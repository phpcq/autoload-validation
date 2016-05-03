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

namespace PhpCodeQuality\AutoloadValidation\Exception;

/**
 * This exception is thrown when a class is already registered in the class map.
 */
class ClassAlreadyRegisteredException extends \RuntimeException
{
    /**
     * The parent class.
     *
     * @var string
     */
    private $className;

    /**
     * The file name where the class was already registered from.
     *
     * @var string
     */
    private $fileName;

    /**
     * Construct the exception.
     *
     * @param string $class    The name of the class that is already registered.
     *
     * @param string $fileName The file where the class was registered from.
     */
    public function __construct($class, $fileName)
    {
        $this->className = $class;
        $this->fileName  = $fileName;

        parent::__construct('Class ' . $class . ' is already registered');
    }

    /**
     * Retrieve the class name.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Retrieve file
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }
}
