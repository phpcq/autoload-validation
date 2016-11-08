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
 * This exception is thrown when a class name is invalid.
 */
class InvalidClassNameException extends \RuntimeException
{
    /**
     * The parent class.
     *
     * @var string
     */
    private $class;

    /**
     * The PHP Version since when the class name became reserved.
     *
     * @var string
     */
    private $since;

    /**
     * Construct the exception.
     *
     * @param string     $class    The name of the class that could not be loaded.
     *
     * @param string     $since    Optionally the PHP version since when the class name became invalid.
     *
     * @param int        $code     The Exception code.
     *
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($class, $since, $code = 0, \Exception $previous = null)
    {
        $this->class = $class;

        parent::__construct('Class name ' . $class . ' is invalid since PHP version ' . $since, $code, $previous);
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

    /**
     * Retrieve the class name.
     *
     * @return string
     */
    public function getPhpVersion()
    {
        return $this->since;
    }
}
