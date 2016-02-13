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
 * This error message is shown when the namespace portion of a psr-0 information is invalid.
 */
class NameSpaceInvalidViolation extends Psr0ValidatorViolation
{
    /**
     * This error message is shown when retrieving the value as text.
     */
    const MESSAGE = <<<EOF
{validatorName}: Invalid namespace value "{psr0Prefix}" found for path "{path}"
EOF;

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
     * @param string $psr0Prefix    The specified namespace.
     *
     * @param string $path          The specified path.
     */
    public function __construct($validatorName, $psr0Prefix, $path)
    {
        parent::__construct($validatorName, $psr0Prefix);
        $this->path = $path;
    }
}
