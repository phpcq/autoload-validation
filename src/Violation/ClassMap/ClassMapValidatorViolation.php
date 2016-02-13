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

namespace PhpCodeQuality\AutoloadValidation\Violation\ClassMap;

use PhpCodeQuality\AutoloadValidation\Violation\ValidatorViolation;

/**
 * This violation is originating from a classmap validator.
 */
abstract class ClassMapValidatorViolation extends ValidatorViolation
{
    /**
     * The specified path.
     *
     * @var string
     */
    protected $classMapPrefix;

    /**
     * Create a new instance.
     *
     * @param string $validatorName  The name of the originating validator.
     *
     * @param string $classMapPrefix The specified classmap namespace prefix.
     */
    public function __construct($validatorName, $classMapPrefix)
    {
        parent::__construct($validatorName);
        $this->classMapPrefix = $classMapPrefix;
    }
}
