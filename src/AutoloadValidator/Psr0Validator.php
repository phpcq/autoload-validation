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
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\ClassFoundInWrongFileViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NameSpaceInvalidViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespacePrefixMismatchViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespaceShouldEndWithBackslashViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NoClassesFoundInPathViolation;

/**
 * This class validates a "psr-0" entry from composer "autoload" sections.
 */
class Psr0Validator extends AbstractValidator
{
    /**
     * {@inheritDoc}
     */
    public function getLoader()
    {
        $loader = new ClassLoader();

        foreach ($this->information as $prefix => $paths) {
            $loader->add(
                $prefix,
                array_map(array($this, 'prependPathWithBaseDir'), (array) $paths)
            );
        }

        return array($loader, 'loadClass');
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
        $subPath = $this->prependPathWithBaseDir($path);
        if (is_numeric($prefix)) {
            $this->report->error(
                new NameSpaceInvalidViolation($this->getName(), $prefix, $path)
            );

            return;
        }

        $classMap = $this->classMapFromPath($subPath, $prefix);

        // All psr-0 namespace prefixes should end with \ unless they are an exact class name or pear style.
        if ($prefix
            && (false !== strpos($prefix, '\\'))
            && !in_array(substr($prefix, -1), array('\\', '_'))
            && !isset($classMap[$prefix])
        ) {
            $this->report->warn(
                new NamespaceShouldEndWithBackslashViolation($this->getName(), $prefix, $path)
            );
        }

        if (empty($classMap)) {
            $this->report->error(
                new NoClassesFoundInPathViolation($this->getName(), $prefix, $subPath)
            );

            return;
        }

        $this->validateClassMap($classMap, $subPath, $prefix, $path);
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
     * @param string $path     The psr-0 top level path.
     *
     * @return void
     */
    private function validateClassMap($classMap, $subPath, $prefix, $path)
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

            // PEAR-like class name or namespace does not match.
            if ($this->checkPearOrNoMatch($classNs, $prefixLength, $cleaned)) {
                // It is allowed to specify a class name as prefix.
                if ($class === $prefix) {
                    continue;
                }
                $this->classMap->remove($class);
                $this->report->error(
                    new NamespacePrefixMismatchViolation(
                        $this->getName(),
                        $prefix,
                        $path,
                        $class,
                        $this->getNameSpaceFromClassName($class)
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
                $this->classMap->remove($class);
                $this->report->error(
                    new ClassFoundInWrongFileViolation(
                        $this->getName(),
                        $prefix,
                        $path,
                        $class,
                        $file,
                        $fileNameShould . $this->getExtensionFromFileName($file)
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
