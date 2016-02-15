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
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ValidatorInterface;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;
use PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException;
use PhpCodeQuality\AutoloadValidation\Report\Report;
use PhpCodeQuality\AutoloadValidation\Violation\ClassAddedMoreThanOnceViolation;

/**
 * This class tests the autoload information.
 */
class AutoloadValidator
{
    /**
     * The list of validators.
     *
     * @var ValidatorInterface[]
     */
    private $validators = array();

    /**
     * The report to write to.
     *
     * @var Report
     */
    private $report;

    /**
     * Create a new instance.
     *
     * @param array  $validators The validators to use.
     *
     * @param Report $report     The report to add to.
     *
     * @throws \InvalidArgumentException If any of the validators is not a validator.
     */
    public function __construct($validators, Report $report)
    {
        $this->report = $report;

        foreach ($validators as $validator) {
            if (!($validator instanceof ValidatorInterface)) {
                throw new \InvalidArgumentException('Invalid validator: ' . get_class($validator));
            }
            $this->validators[] = $validator;
        }
    }

    /**
     * Loop over all validators and validate them.
     *
     * @return void
     */
    public function validate()
    {
        foreach ($this->validators as $validator) {
            $validator->validate();
        }
    }

    /**
     * Retrieve the populated loaders.
     *
     * @return callable[]
     */
    public function getLoaders()
    {
        $loaders = array();
        foreach ($this->validators as $validator) {
            $loaders[$validator->getName()] = $validator->getLoader();
        }

        return $loaders;
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
                    $duplicates[$class][$validatorName] = $file;
                } catch (ClassAlreadyRegisteredException $exception) {
                    $duplicates[$class][$validatorName] = $file;
                }
            }
        }

        foreach ($duplicates as $class => $files) {
            if (count($files) === 1) {
                continue;
            }
            $this->report->error(
                new ClassAddedMoreThanOnceViolation(
                    implode(', ', array_keys($files)),
                    $class,
                    $files
                )
            );
        }

        return $classMap;
    }
}
