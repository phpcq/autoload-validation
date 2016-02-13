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
 * This violation tells that a class has been added more than once.
 */
class ClassAddedMoreThanOnceViolation extends ValidatorViolation
{
    /**
     * This error message is shown when retrieving the value as text.
     */
    const MESSAGE = '{validatorName}: Class {className} is added more than once ({files}).';

    /**
     * The name of the class.
     *
     * @var string
     */
    protected $className;

    /**
     * The files registered for the class.
     *
     * @var string[]
     */
    protected $files;

    /**
     * Create a new instance.
     *
     * @param string    $validatorName The name of the originating validator.
     *
     * @param string    $className     The class in question.
     *
     * @param \string[] $files         The file names where this class is declared in.
     */
    public function __construct($validatorName, $className, array $files)
    {
        parent::__construct($validatorName);
        $this->className = $className;
        $this->files     = $files;
    }
}
