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
 * This interface describes a violation.
 */
interface ViolationInterface
{
    /**
     * Obtain the list of declared parameters in the class.
     *
     * @return array
     */
    public function getParameters();

    /**
     * The default message with placeholders for the parameters.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Return the violation as string representation.
     *
     * @return string
     */
    public function text();
}
