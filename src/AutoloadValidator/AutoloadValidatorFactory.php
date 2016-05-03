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

namespace PhpCodeQuality\AutoloadValidation\AutoloadValidator;

use PhpCodeQuality\AutoloadValidation\ClassMapGenerator;
use PhpCodeQuality\AutoloadValidation\Report\Report;

/**
 * This class creates validators based upon the configuration data.
 */
class AutoloadValidatorFactory
{
    /**
     * The basedir to operate on.
     *
     * @var string
     */
    private $baseDir;

    /**
     * The class map generator to use.
     *
     * @var ClassMapGenerator
     */
    private $generator;

    /**
     * The report to generate.
     *
     * @var Report
     */
    private $report;

    /**
     * Create a new instance.
     *
     * @param string            $baseDir   The base dir.
     *
     * @param ClassMapGenerator $generator The class map generator to use.
     *
     * @param Report            $report    The report to generate.
     */
    public function __construct($baseDir, ClassMapGenerator $generator, Report $report)
    {
        $this->baseDir   = $baseDir;
        $this->generator = $generator;
        $this->report    = $report;
    }

    /**
     * Generate validators for all information from the passed composer.json array.
     *
     * @param array $composer The composer.json data.
     *
     * @return AbstractValidator[]
     */
    public function createFromComposerJson($composer)
    {
        $sections = $this->getAutoloadSectionNames($composer);

        if (empty($sections)) {
            return array();
        }

        $validators = array();
        foreach ($sections as $section) {
            $validators[] = $this->createValidatorsFromSection($section, $composer[$section]);
        }

        return call_user_func_array('array_merge', $validators);
    }

    /**
     * Create an validator.
     *
     * @param string $section     The section name where the autoload information originates from.
     *
     * @param string $type        The autoload information type.
     *
     * @param array  $information The autoload information.
     *
     * @return ClassMapValidator|FilesValidator|Psr0Validator|Psr4Validator
     *
     * @throws \InvalidArgumentException When an unknown loader type has been encountered.
     */
    public function createValidator($section, $type, $information)
    {
        switch ($type) {
            case 'classmap':
                return new ClassMapValidator(
                    $section . '.' . $type,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->report
                );
            case 'files':
                return new FilesValidator(
                    $section . '.' . $type,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->report
                );
            case 'psr-0':
                return new Psr0Validator(
                    $section . '.' . $type,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->report
                );
            case 'psr-4':
                return new Psr4Validator(
                    $section . '.' . $type,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->report
                );
            case 'exclude-from-classmap':
                // No op
                return null;
            default:
                throw new \InvalidArgumentException('Unknown auto loader type ' . $type . ' encountered!');
        }
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
     * @param string $sectionName The name of the autoload section (autoload or autoload-dev).
     *
     * @param array  $section     The autoload section from composer.json.
     *
     * @return AbstractValidator[]
     */
    private function createValidatorsFromSection($sectionName, $section)
    {
        $validators = array();
        foreach ($section as $type => $content) {
            if ($validator = $this->createValidator($sectionName, $type, $content)) {
                $validators[] = $validator;
            }
        }

        return $validators;
    }
}
