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

namespace PhpCodeQuality\AutoloadValidation\Test\AutoloadValidator;

use Composer\Autoload\ClassLoader;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator;

/**
 * This class is for testing the AbstractValidator
 */
class AbstractValidatorMock extends AbstractValidator
{
    /**
     * The name of the validator.
     */
    const NAME = 'validator-mock';

    /**
     * {@inheritDoc}
     */
    protected function doValidate()
    {
        // No op.
    }

    /**
     * {@inheritDoc}
     */
    public function error($message, array $parameters = array())
    {
        parent::error($message, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function warning($message, array $parameters = array())
    {
        parent::warning($message, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function info($message, array $parameters = array())
    {
        parent::info($message, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function addToLoader(ClassLoader $loader)
    {
        // No op.
    }

    /**
     * {@inheritDoc}
     */
    public function cutExtensionFromFileName($file)
    {
        return parent::cutExtensionFromFileName($file);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensionFromFileName($file)
    {
        return parent::getExtensionFromFileName($file);
    }

    /**
     * {@inheritDoc}
     */
    public function getNameSpaceFromClassName($class)
    {
        return parent::getNameSpaceFromClassName($class);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassFromClassName($class)
    {
        return parent::getClassFromClassName($class);
    }

    /**
     * {@inheritDoc}
     */
    public function classMapFromPath($subPath, $namespace = null)
    {
        return parent::classMapFromPath($subPath, $namespace);
    }
}
