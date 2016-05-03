<?php

/**
 * This file is part of phpcq/autoload-validation.
 *
 * (c) 2014 Christian Schiffler, Tristan Lins
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    phpcq/autoload-validation
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2014-2016 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/phpcq/autoload-validation/blob/master/LICENSE MIT
 * @link       https://github.com/phpcq/autoload-validation
 * @filesource
 */

namespace PhpCodeQuality\AutoloadValidation\Hacks;

use PhpCodeQuality\AutoloadValidation\ClassLoader\EnumeratingClassLoader;
use PhpCodeQuality\AutoloadValidation\Exception\ParentClassNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * This class prepares hack loaders.
 */
class HackPreparator
{
    /**
     * The enumerating class loader to add the hacks to.
     *
     * @var EnumeratingClassLoader
     */
    private $enumLoader;

    /**
     * The logger to use.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Create a new instance.
     *
     * @param EnumeratingClassLoader $enumLoader The enum loader to add to.
     *
     * @param LoggerInterface        $logger     The logger to pass messages to.
     */
    public function __construct(EnumeratingClassLoader $enumLoader, LoggerInterface $logger)
    {
        $this->enumLoader = $enumLoader;
        $this->logger     = $logger;
    }

    /**
     * Prepare the loader functions.
     *
     * @param string[] $fileNames The list of file names.
     *
     * @param string[] $pathNames The list of pathes to try (will get suffixed with include_path).
     *
     * @return void
     */
    public function prepareHacks($fileNames, $pathNames)
    {
        if ($dirs = ini_get('include_path')) {
            $pathNames = array_unique(
                array_filter(
                    array_merge(
                        $pathNames,
                        explode(PATH_SEPARATOR, $dirs)
                    )
                )
            );
        }

        foreach ($fileNames as $file) {
            if (null === ($fileName = $this->resolveFile($file, $pathNames))) {
                $this->logger->error(
                    'Custom class loader hack {file} not found and has been ignored.',
                    array('file' => $file)
                );

                return;
            }
            $this->handleHackFile($fileName);
        }
    }

    /**
     * Prepare the Version 1.0 compatible Contao class loader hack.
     *
     * @return void
     *
     * @deprecated Deprecated since 1.1, to be removed in 2.0 - use custom hacks instead.
     */
    public function prepareLegacyHack()
    {
        $logger = $this->logger;

        // Add Contao hack.
        $this->enumLoader->add(function ($class) use ($logger) {
            if (substr($class, 0, 7) !== 'Contao\\') {
                try {
                    spl_autoload_call('Contao\\' . $class);
                } catch (ParentClassNotFoundException $exception) {
                    return null;
                }
                if (EnumeratingClassLoader::isLoaded('Contao\\' . $class)
                    && !EnumeratingClassLoader::isLoaded($class)
                ) {
                    class_alias('Contao\\' . $class, $class);

                    $logger->warning(
                        'Loaded class {class} as {alias} from deprecated Contao hack. ' .
                        'Please specify a custom loader hack if you want to keep this class loaded.',
                        array('class' => 'Contao\\' . $class, 'alias' => $class)
                    );

                    return true;
                }
            }

            return null;
        }, 'contao.hack');
    }

    /**
     * Handle a hack file entry.
     *
     * @param string $fileName The name of the file to handle..
     *
     * @return void
     */
    private function handleHackFile($fileName)
    {
        $logger   = $this->logger;
        $previous = spl_autoload_functions();

        include $fileName;

        $found = $this->determineRegisteredAutoLoader($previous);

        foreach ($found as $index => $loader) {
            $hackName = basename($fileName) . '.' . $index;
            $this->enumLoader->add($this->compileHack($loader, $hackName), $hackName);
        }
        $logger->debug(
            'Custom class loader hack {file} loaded {count} auto load functions.',
            array('file' => $fileName, 'count' => count($found))
        );
    }

    /**
     * Resolve the passed file name.
     *
     * @param string   $fileName  The file name to resolve.
     *
     * @param string[] $pathNames The list of pathes to try (will get suffixed with include_path).
     *
     * @return string|null
     */
    private function resolveFile($fileName, $pathNames)
    {
        foreach ($pathNames as $pathName) {
            if (file_exists($file = $pathName . DIRECTORY_SEPARATOR . $fileName)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Determine the auto load functions registered.
     *
     * @param array $previous The previous autoload functions.
     *
     * @return array
     */
    private function determineRegisteredAutoLoader($previous)
    {
        $found = array();
        $after = spl_autoload_functions();
        foreach ($after as $loader) {
            if (!in_array($loader, $previous)) {
                spl_autoload_unregister($loader);
                $found[] = $loader;
            }
        }

        return $found;
    }

    /**
     * Compile a hack function.
     *
     * @param callable $loader   The loader function.
     *
     * @param string   $hackName The name of the hack.
     *
     * @return \Closure
     */
    private function compileHack($loader, $hackName)
    {
        $logger = $this->logger;

        return function ($class) use ($loader, $hackName, $logger) {
            if (call_user_func($loader, $class)) {
                $logger->debug(
                    'Custom class loader hack {hackName} loaded {class}.',
                    array(
                        'hackName' => $hackName,
                        'class'    => $class
                    )
                );

                return $hackName;
            }

            if (EnumeratingClassLoader::isLoaded($class)) {
                $logger->warning(
                    'Hack {hackName} appears to have loaded {class} but did return null. It SHOULD return true.',
                    array('hackName' => $hackName, 'class' => $class)
                );

                return $hackName;
            }

            return null;
        };
    }
}
