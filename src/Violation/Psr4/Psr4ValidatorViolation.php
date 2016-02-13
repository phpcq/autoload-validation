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

namespace PhpCodeQuality\AutoloadValidation\Violation\Psr4;

use PhpCodeQuality\AutoloadValidation\Violation\ValidatorViolation;

/**
 * This violation is originating from a psr-4 validator.
 */
abstract class Psr4ValidatorViolation extends ValidatorViolation
{
    /**
     * The specified namespace.
     *
     * @var string
     */
    protected $psr4Prefix;

    /**
     * The specified path.
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new instance.
     *
     * @param string $validatorName The name of the originating validator.
     *
     * @param string $psr4Prefix    The specified psr-4 namespace prefix.
     *
     * @param string $path          The specified path.
     */
    public function __construct($validatorName, $psr4Prefix, $path)
    {
        parent::__construct($validatorName);
        $this->psr4Prefix = $psr4Prefix;
        $this->path       = $path;
    }
}
