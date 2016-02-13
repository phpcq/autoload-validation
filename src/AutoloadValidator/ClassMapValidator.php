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
 * This class validates a "class-map" entry from composer "autoload" sections.
 */
class ClassMapValidator extends AbstractValidator
{
    /**
     * This error is shown when the class map entry did not find any classes.
     */
    const ERROR_CLASSMAP_NO_CLASSES_FOUND_FOR_PREFIX = '{name}: No classes found in classmap prefix {prefix}';

    /**
     * {@inheritDoc}
     */
    public function addToLoader(ClassLoader $loader)
    {
        $loader->addClassMap(iterator_to_array($this->getClassMap()));
    }

    /**
     * Check that the auto loading information is correct.
     *
     * {@inheritDoc}
     */
    protected function doValidate()
    {
        // Scan all directories mentioned and validate the class map against the entries.
        foreach ($this->information as $path) {
            $subPath  = str_replace('//', '/', $this->baseDir . '/' . $path);
            $classMap = $this->classMapFromPath($subPath);

            if (empty($classMap)) {
                $this->error(static::ERROR_CLASSMAP_NO_CLASSES_FOUND_FOR_PREFIX, array('prefix' => $subPath));
            }
        }
    }
}
