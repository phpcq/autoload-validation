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
 * This exception is thrown when a class is deprecated.
 */
class DeprecatedClassException extends \RuntimeException
{
    /**
     * The class.
     *
     * @var string
     */
    private $class;

    /**
     * The deprecation message.
     *
     * @var string
     */
    private $deprecationMessage;

    /**
     * Construct the exception.
     *
     * @param string     $class              The name of the class that could not be loaded.
     *
     * @param string     $deprecationMessage The deprecation message.
     *
     * @param int        $code               The Exception code.
     *
     * @param \Exception $previous           The previous exception used for the exception chaining.
     */
    public function __construct($class, $deprecationMessage, $code = 0, \Exception $previous = null)
    {
        $this->class              = $class;
        $this->deprecationMessage = $deprecationMessage;

        parent::__construct(
            'Class ' . $class . ' has been deprecated with reason ' . $deprecationMessage,
            $code,
            $previous
        );
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
     * Retrieve the deprecation message.
     *
     * @return string
     */
    public function getDeprecationMessage()
    {
        return $this->deprecationMessage;
    }
}
