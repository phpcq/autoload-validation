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
 * This class validates a "psr-4" entry from composer "autoload" sections.
 */
class Psr4Validator extends AbstractValidator
{
    /**
     * The name of the validator.
     */
    const NAME = 'psr-4';

    /**
     * This error message is shown when the namespace portion of a psr-4 information is invalid.
     */
    const ERROR_PSR4_NAMESPACE_INVALID = '{name}: Invalid namespace value "{prefix}" found for prefix "{path}"';

    /**
     * Namespace declarations should end in \\ to make sure the autoloader responds exactly.
     *
     * For example Foo would match in FooBar so the trailing backslashes solve the problem:
     * Foo\\ and FooBar\\ are distinct.
     */
    const ERROR_PSR4_NAMESPACE_MUST_END_WITH_BACKSLASH =
        '{name}: Namespace declaration "{prefix}" must end in "\\\\".';

    /**
     * This error message is shown when a psr-0 information does not contain any classes.
     */
    const ERROR_PSR4_NO_CLASSES_FOUND_IN_PATH = '{name}: No classes found for psr-4 {prefix} prefix {path}';

    /**
     * This error message is shown when the namespace of a class does not match the expected psr-0 prefix.
     */
    const ERROR_PSR4_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE =
        '{name}: Class {class} namespace {detected} does not match psr-4 prefix {namespace} for directory {directory}!';

    /**
     * This error is shown when a class has been found in the wrong file.
     */
    const ERROR_PSR4_CLASS_FOUND_IN_WRONG_FILE =
        '{name}: Class {class} found in file {file-is} should reside in file {file-should} (psr-4 prefix {prefix})';

    /**
     * {@inheritDoc}
     */
    public function addToLoader(ClassLoader $loader)
    {
        foreach ($this->information as $prefix => $paths) {
            $loader->addPsr4($prefix, array_map(array($this, 'prependPathWithBaseDir'), (array) $paths));
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
                static::ERROR_PSR4_NAMESPACE_INVALID,
                array('prefix' => $prefix, 'path' => $subPath)
            );

            return;
        }

        if ($prefix && '\\' !== substr($prefix, -1)) {
            $this->error(
                static::ERROR_PSR4_NAMESPACE_MUST_END_WITH_BACKSLASH,
                array('prefix' => $prefix)
            );

            return;
        }

        $classMap = $this->classMapFromPath($subPath, $prefix);

        if (empty($classMap)) {
            $this->error(
                static::ERROR_PSR4_NO_CLASSES_FOUND_IN_PATH,
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
        $cleaned      = $prefix;
        $prefixLength = strlen($cleaned);

        foreach ($classMap as $class => $file) {
            if ('\\' === $class[0]) {
                $class = substr($class, 1);
            }

            if (substr($class, 0, $prefixLength) !== $prefix) {
                $this->error(
                    static::ERROR_PSR4_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'     => $class,
                        'detected'  => $this->getNameSpaceFromClassName($class),
                        'prefix'    => $prefix,
                        'directory' => $subPath
                    )
                );

                continue;
            }

            $fileNameShould = str_replace(
                '//',
                '/',
                $subPath . '/' . str_replace(
                    '\\',
                    '/',
                    substr($class, $prefixLength)
                )
            );

            if ($fileNameShould !== $this->cutExtensionFromFileName($file)) {
                $this->error(
                    static::ERROR_PSR4_CLASS_FOUND_IN_WRONG_FILE,
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
}
