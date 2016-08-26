<?php

/**
 * This file is copied from the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    phpcq/autoload-validation
 * @author     Gyula Sallai <salla016@gmail.com>
 * @author     Jordi Boggiano <j.boggiano@seld.be>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan@lins.io>
 * @copyright  2014-2016 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/phpcq/autoload-validation/blob/master/LICENSE MIT
 * @link       https://github.com/phpcq/autoload-validation
 * @filesource
 */

namespace PhpCodeQuality\AutoloadValidation;

use Symfony\Component\Finder\Finder;

/**
 * A ClassMapGenerator from composer.
 */
class ClassMapGenerator
{
    /**
     * A list of regexes to exclude files.
     *
     * @var string[]
     */
    private $blackListRegex = array();

    /**
     * Create a new instance.
     *
     * @param \string[] $blackListRegex A list of regexes to exclude files.
     */
    public function __construct(array $blackListRegex = array())
    {
        $this->blackListRegex = $blackListRegex;
    }

    /**
     * Iterate over all files in the given directory searching for classes.
     *
     * @param \Iterator|string $path      The path to search in or an iterator.
     *
     * @param string           $whitelist Regex that matches against the file path.
     *
     * @param string           $namespace Optional namespace prefix to filter by.
     *
     * @param string[]         $messages  The error message list to which errors shall be appended to.
     *
     * @return array A class map array
     *
     * @throws \RuntimeException When the path could not be scanned.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function scan($path, $whitelist = null, $namespace = null, &$messages = null)
    {
        return static::createMap($path, $whitelist, $namespace, $messages, $this->blackListRegex);
    }

    /**
     * Generate a class map file.
     *
     * @param \Traversable $dirs      Directories or a single path to search in.
     *
     * @param string       $file      The name of the class map file.
     *
     * @param string[]     $blackList Optional list of blacklist regex for files to exclude.
     *
     * @return void
     */
    public static function dump($dirs, $file, $blackList = null)
    {
        $maps = array();

        foreach ($dirs as $dir) {
            $maps = array_merge($maps, static::createMap($dir, null, null, $blackList));
        }

        file_put_contents($file, sprintf('<?php return %s;', var_export($maps, true)));
    }

    /**
     * Iterate over all files in the given directory searching for classes.
     *
     * @param \Iterator|string $path      The path to search in or an iterator.
     *
     * @param string           $whitelist Regex that matches against the file path.
     *
     * @param string           $namespace Optional namespace prefix to filter by.
     *
     * @param string[]         $messages  The error message list to which errors shall be appended to.
     *
     * @param string[]         $blackList Optional list of blacklist regex for files to exclude.
     *
     * @return array A class map array
     *
     * @throws \RuntimeException When the path could not be scanned.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function createMap($path, $whitelist = null, $namespace = null, &$messages = null, $blackList = null)
    {
        if (is_string($path)) {
            if (is_file($path)) {
                $path = array(new \SplFileInfo($path));
            } elseif (is_dir($path)) {
                $path = Finder::create()->files()->followLinks()->name('/\.(php|inc|hh)$/')->in($path);
            } else {
                throw new \RuntimeException(
                    'Could not scan for classes inside "'.$path.
                    '" which does not appear to be a file nor a folder'
                );
            }
        }

        $map = array();

        foreach ($path as $file) {
            $filePath = $file->getRealPath();

            if ($blackList && self::pathMatchesRegex($filePath, $blackList)) {
                continue;
            }

            if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), array('php', 'inc', 'hh'))) {
                continue;
            }

            if ($whitelist && !preg_match($whitelist, strtr($filePath, '\\', '/'))) {
                continue;
            }

            $classes = self::findClasses($filePath);

            foreach ($classes as $class) {
                // skip classes not within the given namespace prefix
                if (null !== $namespace && 0 !== strpos($class, $namespace)) {
                    continue;
                }

                if (!isset($map[$class])) {
                    $map[$class] = $filePath;
                } elseif (null !== $messages
                    && ($map[$class] !== $filePath)
                    && !preg_match('{/(test|fixture|example)s?/}i', strtr($map[$class].' '.$filePath, '\\', '/'))
                ) {
                    $messages[] = '<warning>Warning: Ambiguous class resolution, "'.$class.'"'.
                        ' was found in both "'.$map[$class].'" and "'.$filePath.'", the first will be used.</warning>';
                }
            }
        }

        return $map;
    }

    /**
     * Test path against blacklist regex list.
     *
     * @param string   $path      The path to check.
     *
     * @param string[] $blackList List of blacklist regexes.
     *
     * @return bool
     */
    private static function pathMatchesRegex($path, $blackList)
    {
        foreach ($blackList as $item) {
            $match = '#' . strtr($item, '#', '\#') . '#';
            if (preg_match($match, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the classes in the given file.
     *
     * @param string $path The file to check.
     *
     * @throws \RuntimeException When the file does not exist or is not accessible.
     *
     * @return array The found classes.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private static function findClasses($path)
    {
        $extraTypes = PHP_VERSION_ID < 50400 ? '' : '|trait';
        if (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.3', '>=')) {
            $extraTypes .= '|enum';
        }

        // @codingStandardsIgnoreStart
        $contents = @php_strip_whitespace($path);
        // @codingStandardsIgnoreEnd
        if (!$contents) {
            if (!file_exists($path)) {
                $message = 'File at "%s" does not exist, check your classmap definitions';
            } elseif (!is_readable($path)) {
                $message = 'File at "%s" is not readable, check its permissions';
            } elseif ('' === trim(file_get_contents($path))) {
                // The input file was really empty and thus contains no classes
                return array();
            } else {
                $message = 'File at "%s" could not be parsed as PHP, it may be binary or corrupted';
            }
            $error = error_get_last();
            if (isset($error['message'])) {
                $message .= PHP_EOL . 'The following message may be helpful:' . PHP_EOL . $error['message'];
            }
            throw new \RuntimeException(sprintf($message, $path));
        }

        // return early if there is no chance of matching anything in this file
        if (!preg_match('{\b(?:class|interface'.$extraTypes.')\s}i', $contents)) {
            return array();
        }

        $matches = self::extractClasses($contents, $extraTypes);
        if (array() === $matches) {
            return array();
        }

        $classes   = array();
        $namespace = '';

        for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
            if (!empty($matches['ns'][$i])) {
                $namespace = str_replace(array(' ', "\t", "\r", "\n"), '', $matches['nsname'][$i]) . '\\';
            } else {
                $name = $matches['name'][$i];
                // skip anon classes extending/implementing
                if ($name === 'extends' || $name === 'implements') {
                    continue;
                }
                if ($name[0] === ':') {
                    // This is an XHP class, https://github.com/facebook/xhp
                    $name = 'xhp'.substr(str_replace(array('-', ':'), array('_', '__'), $name), 1);
                } elseif ($matches['type'][$i] === 'enum') {
                    // In Hack, something like:
                    // enum Foo: int { HERP = '123'; }
                    // The regex above captures the colon, which isn't part of
                    // the class name.
                    $name = rtrim($name, ':');
                }
                $classes[] = ltrim($namespace . $name, '\\');
            }
        }

        return $classes;
    }

    /**
     * Prepare the file contents.
     *
     * @param string $contents   The file contents.
     *
     * @param string $extraTypes The extra types to match.
     *
     * @return array
     */
    private static function extractClasses($contents, $extraTypes)
    {
        // strip heredocs/nowdocs
        $contents = preg_replace(
            '{<<<\s*(\'?)(\w+)\\1(?:\r\n|\n|\r)(?:.*?)(?:\r\n|\n|\r)\\2(?=\r\n|\n|\r|;)}s',
            'null',
            $contents
        );
        // strip strings
        $contents = preg_replace(
            '{"[^"\\\\]*+(\\\\.[^"\\\\]*+)*+"|\'[^\'\\\\]*+(\\\\.[^\'\\\\]*+)*+\'}s',
            'null',
            $contents
        );
        // strip leading non-php code if needed
        if (substr($contents, 0, 2) !== '<?') {
            $contents = preg_replace('{^.+?<\?}s', '<?', $contents, 1, $replacements);
            if ($replacements === 0) {
                return array();
            }
        }
        // strip non-php blocks in the file
        $contents = preg_replace('{\?>.+<\?}s', '?><?', $contents);
        // strip trailing non-php code if needed
        $pos = strrpos($contents, '?>');
        if (false !== $pos && false === strpos(substr($contents, $pos), '<?')) {
            $contents = substr($contents, 0, $pos);
        }

        preg_match_all(
            '{
            (?:
                 \b(?<![\$:>])(?P<type>class|interface' . $extraTypes .
            ') \s+ (?P<name>[a-zA-Z_\x7f-\xff:][a-zA-Z0-9_\x7f-\xff:\-]*)
               | \b(?<![\$:>])(?P<ns>namespace) (?P<nsname>\s+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*' .
            '(?:\s*\\\\\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*)? \s*[\{;]
            )
        }ix',
            $contents,
            $matches
        );

        return $matches;
    }
}
