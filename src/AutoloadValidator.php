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

namespace PhpCodeQuality\AutoloadValidation;

use Composer\Autoload\ClassLoader;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;
use PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException;
use Psr\Log\LoggerInterface;

/**
 * This class tests the autoload information.
 */
class AutoloadValidator
{
    /**
     * The list of validators.
     *
     * @var AbstractValidator[]
     */
    private $validators = array();

    /**
     * The logger to use.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Create a new instance.
     *
     * @param array           $validators The validators to use.
     *
     * @param LoggerInterface $logger     The logger to use.
     *
     * @throws \InvalidArgumentException If any of the validators is not a validator.
     */
    public function __construct($validators, LoggerInterface $logger)
    {
        $this->logger = $logger;

        foreach ($validators as $validator) {
            if (!($validator instanceof AbstractValidator)) {
                throw new \InvalidArgumentException('Invalid validator: ' . get_class($validator));
            }
            $this->validators[] = $validator;
        }
    }

    /**
     * Loop over all validators and validate them.
     *
     * @return bool
     */
    public function validate()
    {
        $result = true;

        foreach ($this->validators as $validator) {
            if ($validator->hasErrors()) {
                $this->logger->error(
                    'The {name} information in composer.json is incorrect!',
                    array('name' => $validator->getName())
                );

                $result = false;
            }
        }

        return $result;
    }

    /**
     * Retrieve the populated loader.
     *
     * @return ClassLoader
     */
    public function getLoader()
    {
        $loader = new ClassLoader();
        foreach ($this->validators as $validator) {
            $validator->addToLoader($loader);
        }

        return $loader;
    }

    /**
     * Retrieve a class map containing all the class maps from all registered validators.
     *
     * @return ClassMap
     */
    public function getClassMap()
    {
        $classMap   = new ClassMap();
        $duplicates = array();

        foreach ($this->validators as $validator) {
            $validatorName   = $validator->getName();
            $partialClassMap = $validator->getClassMap();
            foreach ($partialClassMap as $class => $file) {
                try {
                    $classMap->add($class, $file);
                } catch (ClassAlreadyRegisteredException $exception) {
                    $duplicates[$class][$validatorName] = $file;
                }
            }
        }

        foreach ($duplicates as $class => $files) {
            $autoloaders = array();
            foreach ($files as $loader => $file) {
                $autoloaders[] = $loader . ' ' . $file;
            }

            $this->logger->error(
                'The class {class} is available via multiple autoloader values:' . "\n" . '{autoloaders}',
                array('class' => $class, 'autoloaders' => implode("\n", $autoloaders))
            );
        }

        return $classMap;
    }
}
