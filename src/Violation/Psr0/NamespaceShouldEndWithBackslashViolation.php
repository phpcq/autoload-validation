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
 * Namespace declarations should end in \\ to make sure the auto loader responds exactly.
 *
 * For example Foo would match in FooBar so the trailing backslashes solve the problem:
 * Foo\\ and FooBar\\ are distinct.
 */
class NamespaceShouldEndWithBackslashViolation extends Psr0ValidatorViolation
{
    /**
     * Namespace declarations should end in \\ to make sure the auto loader responds exactly.
     */
    const MESSAGE = <<<EOF
{validatorName}: Namespace declaration "{psr0Prefix}" should end in "\\" to ensure the auto loader responds exactly.
For example "Foo" would match in "FooBar" so add trailing backslash to solve the problem:
"Foo\\" and "FooBar\\" are distinct.
EOF;
}
