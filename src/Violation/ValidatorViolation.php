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
 * This violation is originating from a validator.
 */
abstract class ValidatorViolation extends AbstractViolation
{
    /**
     * Name of the originating validator.
     *
     * @var string
     */
    protected $validatorName;

    /**
     * Create a new instance.
     *
     * @param string $validatorName The name of the originating validator.
     */
    public function __construct($validatorName)
    {
        $this->validatorName = $validatorName;
    }
}
