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

namespace PhpCodeQuality\AutoloadValidation\AutoloadValidator;

use Composer\Autoload\ClassLoader;

/**
 * This class validates a "psr-0" entry from composer "autoload" sections.
 */
class Psr0Validator extends AbstractValidator
{
    /**

    /**
     * This error message is shown when the namespace portion of a psr-0 information is invalid.
     */
    const ERROR_PSR0_NAMESPACE_INVALID = '{name}: Invalid namespace value "{prefix}" found for prefix "{path}"';

    /**
     * Namespace declarations should end in \\ to make sure the autoloader responds exactly.
     *
     * For example Foo would match in FooBar so the trailing backslashes solve the problem:
     * Foo\\ and FooBar\\ are distinct.
     */
    const WARN_PSR0_NAMESPACE_SHOULD_END_WITH_BACKSLASH =
        '{name}: Namespace declaration "{prefix}" should end in "\\\\" to make sure the auto loader responds exactly.';

    /**
     * This error message is shown when a psr-0 information does not contain any classes.
     */
    const ERROR_PSR0_NO_CLASSES_FOUND_IN_PATH = '{name}: No classes found for psr-0 {prefix} prefix {path}';

    /**
     * This error message is shown when the namespace of a class does not match the expected psr-0 prefix.
     */
    const ERROR_PSR0_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE =
        '{name}: Class {class} namespace {detected} does not match psr-0 prefix {namespace} for directory {directory}!';

    /**
     * This error is shown when a class name is used as psr-0 prefix.
     */
    const ERROR_PSR0_CLASS_USED_AS_PSR0_PREFIX =
        '{name}: Class {class} is used as psr-0 namespace prefix {prefix} for directory {directory}!';

    /**
     * This error is shown when a class has been found in the wrong file.
     */
    const ERROR_PSR0_CLASS_FOUND_IN_WRONG_FILE =
        "{name}: (psr-0 Prefix {prefix})\n Found class: {class} \n in file:    {file-is}\n should be:  {file-should}";

    /**
     * {@inheritDoc}
     */
    public function addToLoader(ClassLoader $loader)
    {
        foreach ($this->information as $prefix => $paths) {
            $loader->add(
                $prefix,
                array_map(array($this, 'prependPathWithBaseDir'), (array) $paths)
            );
        }
    }

    /**
     * Check that the auto loading information is correct.
     *
     * {@inheritDoc}
     */
    protected function doValidate()
    {
        foreach ($this->information as $prefix => $paths) {
            foreach ((array) $paths as $path) {
                $this->doValidatePath($prefix, $path);
            }
        }
    }

    /**
     * Validate a path.
     *
     * @param string $prefix The prefix to use for this path.
     *
     * @param string $path   The path.
     *
     * @return void
     */
    private function doValidatePath($prefix, $path)
    {
        $subPath = str_replace('//', '/', $this->baseDir . '/' . $path);
        if (is_numeric($prefix)) {
            $this->error(
                static::ERROR_PSR0_NAMESPACE_INVALID,
                array('prefix' => $prefix, 'path' => $subPath)
            );

            return;
        }

        $classMap = $this->classMapFromPath($subPath, $prefix);

        // All psr-0 namespace prefixes should end with \ unless they are an exact class name.
        if ($prefix && '\\' !== substr($prefix, -1) && !isset($classMap[$prefix])) {
            $this->warning(
                static::WARN_PSR0_NAMESPACE_SHOULD_END_WITH_BACKSLASH,
                array('prefix' => $prefix)
            );
        }

        if (empty($classMap)) {
            $this->error(
                static::ERROR_PSR0_NO_CLASSES_FOUND_IN_PATH,
                array('prefix' => $prefix, 'path' => $subPath)
            );

            return;
        }

        $this->validateClassMap($classMap, $subPath, $prefix);
    }

    /**
     * Validate the passed classmap.
     *
     * @param array  $classMap The list of classes.
     *
     * @param string $subPath  The path where the classes are contained within.
     *
     * @param string $prefix   The psr-0 top level namespace.
     *
     * @return void
     */
    private function validateClassMap($classMap, $subPath, $prefix)
    {
        $cleaned = $prefix;
        if ('\\' === substr($prefix, -1)) {
            $cleaned = substr($prefix, 0, -1);
        }
        $prefixLength = strlen($cleaned);

        foreach ($classMap as $class => $file) {
            if ('\\' === $class[0]) {
                $class = substr($class, 1);
            }
            $classNs = $this->getNameSpaceFromClassName($class);
            $classNm = $this->getClassFromClassName($class);

            if ($class === $prefix) {
                /*
                Why was this here? It is legal to specify an exact class as psr-0, according to the specs.
                $this->error(
                    static::ERROR_PSR0_CLASS_USED_AS_PSR0_PREFIX,
                    array(
                        'class'     => $class,
                        'prefix'    => $prefix,
                        'directory' => $subPath
                    )
                );
                */

                continue;
            }

            // PEAR-like class name or namespace does not match.
            if ($this->checkPearOrNoMatch($classNs, $prefixLength, $cleaned)) {
                $this->error(
                    static::ERROR_PSR0_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'     => $class,
                        'detected'  => $this->getNameSpaceFromClassName($class),
                        'prefix'    => $prefix,
                        'directory' => $subPath
                    )
                );
                continue;
            }

            $classNm        = ltrim('\\' . $classNm, '\\');
            $fileNameShould = rtrim($subPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($classNs) {
                $fileNameShould .= str_replace('\\', DIRECTORY_SEPARATOR, $classNs) . DIRECTORY_SEPARATOR;
            }

            $fileNameShould .= str_replace('_', DIRECTORY_SEPARATOR, $classNm);

            if ($fileNameShould !== $this->cutExtensionFromFileName($file)) {
                $this->error(
                    static::ERROR_PSR0_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class' => $class,
                        'file-is' => $file,
                        'file-should' => $fileNameShould . $this->getExtensionFromFileName($file),
                        'prefix' => $prefix,
                    )
                );
            }
        }
    }

    /**
     * Check if the class is PEAR style or does not match at all.
     *
     * Return true if there is some problem, false otherwise.
     *
     * @param string $classNameSpace The namespace of the class.
     *
     * @param string $prefixLength   The length of the namespace that must match.
     *
     * @param string $prefix         The required namespace prefix.
     *
     * @return bool
     */
    private function checkPearOrNoMatch($classNameSpace, $prefixLength, $prefix)
    {
        // No NS, separator in class namespace.
        if (!$classNameSpace) {
            return false;
        }

        if (substr($classNameSpace, 0, $prefixLength) === $prefix) {
            return false;
        }

        return true;
    }
}
