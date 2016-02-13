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
 * This interface describes an autoload validator.
 */
interface ValidatorInterface
{
    /**
     * Parse the autoload information.
     *
     * @return void
     */
    public function validate();

    /**
     * Retrieve the generated classmap.
     *
     * @return ClassMap
     */
    public function getClassMap();

    /**
     * Retrieve the name.
     *
     * @return string
     */
    public function getName();

    /**
     * Add the auto loading to the passed loader.
     *
     * @param ClassLoader $loader The loader to add to.
     *
     * @return void
     */
    public function addToLoader(ClassLoader $loader);
}
