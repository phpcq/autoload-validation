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
 * Namespace declarations must end in \\ to make sure the auto loader responds exactly.
 */
class NamespaceMustEndWithBackslashViolation extends AbstractPsr4Violation
{
    /**
     * The message that shall end up in logs.
     */
    const MESSAGE = <<<EOF
{validatorName}: Namespace declaration "{psr4Prefix}" must end in "\\" to ensure the auto loader responds exactly.
EOF;
}
