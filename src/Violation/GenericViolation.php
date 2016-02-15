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

namespace PhpCodeQuality\AutoloadValidation\Violation;

/**
 * This violation is a generic base type.
 */
class GenericViolation extends AbstractViolation
{
    /**
     * The message.
     *
     * @var string
     */
    private $message;

    /**
     * The parameters.
     *
     * @var array
     */
    private $parameters;

    /**
     * Create a new instance.
     *
     * @param string $message    The message to use.
     *
     * @param array  $parameters The parameters to use.
     */
    public function __construct($message, array $parameters = array())
    {
        $this->message    = $message;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage()
    {
        return $this->message;
    }
}
