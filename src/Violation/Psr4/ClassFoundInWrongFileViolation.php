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
 * This violation is shown when a class has been found in the wrong file.
 */
class ClassFoundInWrongFileViolation extends Psr4ValidatorViolation
{
    /**
     * This error message is shown when retrieving the value as text.
     */
    const MESSAGE = <<<EOF
{validatorName}: (prefix {psr4Prefix})
found class: {class}
in file:     {fileIs}
should be:   {fileShould}
EOF;

    /**
     * The class in question.
     *
     * @var string
     */
    protected $class;

    /**
     * The file name where it has been found.
     *
     * @var string
     */
    protected $fileIs;

    /**
     * The file name where it should have been found.
     *
     * @var string
     */
    protected $fileShould;

    /**
     * Create a new instance.
     *
     * @param string $validatorName The name of the originating validator.
     *
     * @param string $psr4Prefix    The specified psr-4 namespace prefix.
     *
     * @param string $path          The specified path.
     *
     * @param string $class         The class in question.
     *
     * @param string $fileIs        The file name where it has been found.
     *
     * @param string $fileShould    The file name where it should have been found.
     */
    public function __construct($validatorName, $psr4Prefix, $path, $class, $fileIs, $fileShould)
    {
        parent::__construct($validatorName, $psr4Prefix, $path);

        $this->class      = $class;
        $this->fileIs     = $fileIs;
        $this->fileShould = $fileShould;
    }
}
