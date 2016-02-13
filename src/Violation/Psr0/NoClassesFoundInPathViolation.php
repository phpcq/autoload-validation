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

namespace PhpCodeQuality\AutoloadValidation\Violation\Psr0;

/**
 * This error message is shown when a path specified in a psr-0 information does not contain any classes.
 */
class NoClassesFoundInPathViolation extends Psr0ValidatorViolation
{
    /**
     * This error message is shown when a scanned path from a validator does not contain any classes.
     */
    const MESSAGE = '{validatorName}: No classes found in {path} for psr-0 prefix {psr0Prefix}';

    /**
     * The path where no classes have been found.
     *
     * @var string[]
     */
    protected $path;

    /**
     * Create a new instance.
     *
     * @param string $validatorName The name of the originating validator.
     *
     * @param string $psr0Prefix    The specified psr-0 namespace prefix.
     *
     * @param string $path          The path where no classes have been found.
     */
    public function __construct($validatorName, $psr0Prefix, $path)
    {
        parent::__construct($validatorName, $psr0Prefix);
        $this->path = $path;
    }
}
