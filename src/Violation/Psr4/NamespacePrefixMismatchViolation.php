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
 * This violation is shown when the namespace of a class does not match the expected psr-4 prefix.
 */
class NamespacePrefixMismatchViolation extends AbstractPsr4Violation
{
    /**
     * The message that shall end up in logs.
     */
    const MESSAGE = <<<EOF
{validatorName}: Class {class} namespace {namespace} does not match prefix {psr4Prefix} for directory {path}!
EOF;

    /**
     * The class name.
     *
     * @var string
     */
    protected $class;

    /**
     * The namespace of the class.
     *
     * @var string
     */
    protected $namespace;

    /**
     * Create a new instance.
     *
     * @param string $validatorName The name of the originating validator.
     *
     * @param string $psr4Prefix    The specified psr-0 namespace prefix.
     *
     * @param string $path          The path where the class has been found.
     *
     * @param string $class         The class name.
     *
     * @param string $namespace     The namespace of the class.
     */
    public function __construct($validatorName, $psr4Prefix, $path, $class, $namespace)
    {
        parent::__construct($validatorName, $psr4Prefix, $path);
        $this->class     = $class;
        $this->namespace = $namespace;
    }
}
