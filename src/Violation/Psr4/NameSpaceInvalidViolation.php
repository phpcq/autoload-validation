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

/**
 * This error message is shown when the namespace portion of a psr-4 information is invalid.
 */
class NameSpaceInvalidViolation extends AbstractPsr4Violation
{
    /**
     * This error message is shown when retrieving the value as text.
     */
    const MESSAGE = <<<EOF
{validatorName}: Invalid namespace value "{psr4Prefix}" found for path "{path}"
EOF;
}
