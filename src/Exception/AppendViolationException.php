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

use Exception;

/**
 * This exception is thrown from the Report class when one or more destinations threw an exception when appending a
 * violation.
 */
class AppendViolationException extends \RuntimeException
{
    /**
     * The exceptions that where thrown.
     *
     * @var \Exception[]
     */
    private $exceptions;

    /**
     * Create the exception.
     *
     * @param string      $message    The message to use.
     *
     * @param Exception[] $exceptions The exceptions the destinations threw.
     *
     * @param int         $code       The exception code.
     */
    public function __construct($message, $exceptions, $code = 0)
    {
        $this->exceptions = $exceptions;
        parent::__construct($message, $code);
    }

    /**
     * Retrieve the exceptions that where thrown.
     *
     * @return \Exception[]
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }
}
