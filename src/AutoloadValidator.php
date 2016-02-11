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
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AutoloadValidatorFactory;
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
     * @param array                    $information The information from composer.json.
     *
     * @param AutoloadValidatorFactory $factory     The class map generator to use.
     *
     * @param LoggerInterface          $logger      The logger to use.
     */
    public function __construct($information, AutoloadValidatorFactory $factory, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $sections = $this->getAutoloadSectionNames($information);

        if (empty($sections)) {
            $this->logger->info('No autoload information found, skipping test.');
            return;
        }

        foreach ($sections as $section) {
            $this->createValidatorsFromSection($factory, $section, $information[$section]);
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


    /**
     * Ensure that a composer autoload section is present.
     *
     * @param array $composer The composer json contents.
     *
     * @return string[]
     */
    private function getAutoloadSectionNames($composer)
    {
        $sections = array();

        if (array_key_exists('autoload', $composer)) {
            $sections[] = 'autoload';
        }

        if (array_key_exists('autoload-dev', $composer)) {
            $sections[] = 'autoload-dev';
        }

        return $sections;
    }

    /**
     * Create all validators for the passed section.
     *
     * @param AutoloadValidatorFactory $factory     The class map generator to use.
     *
     * @param string                   $sectionName The name of the autoload section (autoload or autoload-dev).
     *
     * @param array                    $section     The autoload section from composer.json.
     *
     * @return void
     */
    private function createValidatorsFromSection(AutoloadValidatorFactory $factory, $sectionName, $section)
    {
        foreach ($section as $type => $content) {
            $this->validators[] = $factory->createValidator($sectionName, $type, $content);
        }
    }
}
