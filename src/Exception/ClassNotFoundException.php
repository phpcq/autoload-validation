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

namespace PhpCodeQuality\AutoloadValidation\Exception;

/**
 * This exception is thrown when a class could not be loaded.
 */
class ClassNotFoundException extends \RuntimeException
{
    /**
     * The parent class.
     *
     * @var string
     */
    private $class;

    /**
     * Construct the exception.
     *
     * @param string     $class    The name of the class that could not be loaded.
     *
     * @param int        $code     The Exception code.
     *
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($class, $code = 0, \Exception $previous = null)
    {
        $this->class = $class;

        parent::__construct('Class ' . $class . ' could not be loaded', $code, $previous);
    }

    /**
     * Retrieve the class name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
