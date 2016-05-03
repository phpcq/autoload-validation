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
 * This is the base class for a violation.
 */
abstract class AbstractViolation implements ViolationInterface
{
    /**
     * The message that shall end up in logs.
     */
    const MESSAGE = 'Message not defined.';

    /**
     * {@inheritDoc}
     */
    public function text()
    {
        return $this->interpolate($this->getMessage(), $this->getParameters());
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters()
    {
        $reflection = new \ReflectionClass($this);

        $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);
        $parameters = array();
        foreach ($properties as $property) {
            $name = $property->getName();

            $parameters[$name] = $this->{$name};
        }

        return $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage()
    {
        return static::MESSAGE;
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message The message to interpolate.
     *
     * @param array  $context The context to use.
     *
     * @return string
     *
     * @author PHP Framework Interoperability Group
     */
    private function interpolate($message, array $context)
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace[sprintf('{%s}', $key)] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
