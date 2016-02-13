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
use PhpCodeQuality\AutoloadValidation\Violation\Psr4\ClassFoundInWrongFileViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr4\NameSpaceInvalidViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr4\NamespaceMustEndWithBackslashViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr4\NamespacePrefixMismatchViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr4\NoClassesFoundInPathViolation;

/**
 * This class validates a "psr-4" entry from composer "autoload" sections.
 */
class Psr4Validator extends AbstractValidator
{
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
            $this->report->error(new NameSpaceInvalidViolation($this->getName(), $prefix, $path));

            return;
        }

        if ($prefix && '\\' !== substr($prefix, -1)) {
            $this->report->error(new NamespaceMustEndWithBackslashViolation($this->getName(), $prefix, $path));

            return;
        }

        $classMap = $this->classMapFromPath($subPath, $prefix);
        if (empty($classMap)) {
            $this->report->error(
                new NoClassesFoundInPathViolation($this->getName(), $prefix, $path)
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
                $this->report->error(
                    new NamespacePrefixMismatchViolation(
                        $this->getName(),
                        $prefix,
                        $subPath,
                        $class,
                        $this->getNameSpaceFromClassName($class)
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
                $this->report->error(
                    new ClassFoundInWrongFileViolation(
                        $this->getName(),
                        $prefix,
                        $subPath,
                        $class,
                        $file,
                        $fileNameShould . $this->getExtensionFromFileName($file)
                    )
                );
            }
        }
    }
}
