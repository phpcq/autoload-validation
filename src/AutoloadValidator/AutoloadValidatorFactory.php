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
            'Creating {type} validator for {content}',
            array('type' => $type, 'content' => var_export($information, true))
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
}
