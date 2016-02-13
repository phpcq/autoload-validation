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
use Psr\Log\LoggerInterface;

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
     * The logger to use.
     *
     * @var LoggerInterface
     */
    private $logger;
     * Create a new instance.
     *
     * @param string            $baseDir   The base dir.
     *
     * @param ClassMapGenerator $generator The class map generator to use.
     *
     * @param LoggerInterface   $logger    The logger to use.
     */
    public function __construct($baseDir, ClassMapGenerator $generator, LoggerInterface $logger)
    {
        $this->baseDir   = $baseDir;
        $this->generator = $generator;
        $this->logger    = $logger;
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
            $this->logger->info('No autoload information found, skipping test.');
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
        $this->logger->debug(
            'Creating {name}.{type} validator with configuration {content}',
            array('name' => $section, 'type' => $type, 'content' => $information)
        );
        switch ($type) {
            case ClassMapValidator::NAME:
                return new ClassMapValidator(
                    $section,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->logger
                );
            case FilesValidator::NAME:
                return new FilesValidator(
                    $section,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->logger
                );
            case Psr0Validator::NAME:
                return new Psr0Validator(
                    $section,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->logger
                );
            case Psr4Validator::NAME:
                return new Psr4Validator(
                    $section,
                    $information,
                    $this->baseDir,
                    $this->generator,
                    $this->logger
                );
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
            $validators[] = $this->createValidator($sectionName, $type, $content);
        }

        return $validators;
    }
}
