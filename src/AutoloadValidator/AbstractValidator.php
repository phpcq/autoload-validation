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

use Composer\Autoload\ClassLoader;
use PhpCodeQuality\AutoloadValidation\ClassMapGenerator;
use PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException;
use PhpCodeQuality\AutoloadValidation\Report\Report;
use PhpCodeQuality\AutoloadValidation\Violation\ClassAddedMoreThanOnceViolation;
use Psr\Log\LoggerInterface;

/**
 * This class is the abstract base for all validators.
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * The base dir to operate on.
     *
     * @var string
     */
    protected $baseDir;

    /**
     * The class map of classes loadable with this loader.
     *
     * @var ClassMap
     */
    protected $classMap;

    /**
     * Flag if errors were detected.
     *
     * @var bool
     */
    protected $errored = false;

    /**
     * The class map generator to use.
     *
     * @var ClassMapGenerator
     */
    protected $generator;

    /**
     * The information to validate.
     *
     * @var mixed
     */
    protected $information;

    /**
     * The logger to use.
     *
     * @var LoggerInterface
     *
     * @deprecated
     */
    public $logger;

    /**
     * The name of the autoload section.
     *
     * @var string
     */
    protected $name;

    /**
     * Local flag if validation has been done yet.
     *
     * @var bool
     */
    private $validated = false;

    /**
     * The report to generate.
     *
     * @var Report
     */
    protected $report;

    /**
     * Create a new instance.
     *
     * @param string            $name        The name of the autoload section.
     *
     * @param mixed             $information The information to validate.
     *
     * @param string            $baseDir     The base dir to operate on.
     *
     * @param ClassMapGenerator $generator   The class map generator to use.
     *
     * @param Report            $report      The report to generate.
     */
    public function __construct($name, $information, $baseDir, ClassMapGenerator $generator, Report $report)
    {
        $this->name        = $name;
        $this->information = $information;
        $this->baseDir     = $baseDir;
        $this->generator   = $generator;
        $this->classMap    = new ClassMap();
        $this->report      = $report;
    }

    /**
     * Parse the autoload information.
     *
     * @return void
     */
    public function validate()
    {
        if ($this->validated) {
            return;
        }

        $this->doValidate();
        $this->validated = true;
    }

    /**
     * Retrieve the generated classmap.
     *
     * @return ClassMap
     */
    public function getClassMap()
    {
        $this->validate();

        return $this->classMap;
    }

    /**
     * Check if errors have been detected.
     *
     * @return bool
     */
    public function hasErrors()
    {
        $this->validate();

        return $this->errored;
    }

    /**
     * Retrieve the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add the auto loading to the passed loader.
     *
     * @param ClassLoader $loader The loader to add to.
     *
     * @return void
     */
    abstract public function addToLoader(ClassLoader $loader);

    /**
     * Errors.
     *
     * @param string $message    The message to add.
     *
     * @param array  $parameters The error parameters. NOTE: the parameter "name" will always be populated.
     *
     * @return void
     *
     * @deprecated
     */
    protected function error($message, array $parameters = array())
    {
        $parameters['name'] = $this->name;

        if ($this->logger) {
            $this->logger->error($message, $parameters);
        }

        $this->errored = true;
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message    The message to add.
     *
     * @param array  $parameters The error parameters. NOTE: the parameter "name" will always be populated.
     *
     * @return void
     *
     * @deprecated
     */
    protected function warning($message, array $parameters = array())
    {
        $parameters['name'] = $this->name;

        if ($this->logger) {
            $this->logger->warning($message, $parameters);
        }
    }

    /**
     * Interesting events.
     *
     * @param string $message    The message to add.
     *
     * @param array  $parameters The error parameters. NOTE: the parameter "name" will always be populated.
     *
     * @return void
     *
     * @deprecated
     */
    protected function info($message, array $parameters = array())
    {
        $parameters['name'] = $this->name;

        if ($this->logger) {
            $this->logger->info($message, $parameters);
        }
    }

    /**
     * Prepend the passed path with the base dir.
     *
     * @param string $path The path to prepend.
     *
     * @return string
     */
    protected function prependPathWithBaseDir($path)
    {
        return str_replace('//', '/', $this->baseDir . '/' . $path);
    }

    /**
     * Create a class map.
     *
     * @param string      $subPath   The path.
     *
     * @param string|null $namespace The namespace prefix (optional).
     *
     * @return array
     */
    protected function classMapFromPath($subPath, $namespace = null)
    {
        $messages = array();
        $classMap = $this->generator->scan($subPath, null, $namespace, $messages);

        if ($messages) {
            foreach ($messages as $message) {
                $this->warning($message);
            }
        }

        foreach ($classMap as $class => $file) {
            try {
                $this->classMap->add($class, $file);
            } catch (ClassAlreadyRegisteredException $exception) {
                $this->report->append(
                    new ClassAddedMoreThanOnceViolation(
                        $this->getName(),
                        $class,
                        array(
                            $exception->getFileName(),
                            $file
                        )
                    )
                );
            }
        }

        return $classMap;
    }

    /**
     * Cut the file extension from the filename and return the result.
     *
     * @param string $file The file name.
     *
     * @return string
     */
    protected function cutExtensionFromFileName($file)
    {
        return preg_replace('/\\.[^.\\s]{2,3}$/', '', $file);
    }

    /**
     * Cut the file extension from the filename and return the result.
     *
     * @param string $file The file name.
     *
     * @return string
     */
    protected function getExtensionFromFileName($file)
    {
        return preg_replace('/^.*(\\.[^.\\s]{2,3})$/', '$1', $file);
    }

    /**
     * Get the namespace name from the full class name.
     *
     * @param string $class The full class name.
     *
     * @return string
     */
    protected function getNameSpaceFromClassName($class)
    {
        $chunks = explode('\\', $class);
        array_pop($chunks);

        return implode('\\', $chunks);
    }

    /**
     * Get the class name from the full class name.
     *
     * @param string $class The full class name.
     *
     * @return string
     */
    protected function getClassFromClassName($class)
    {
        $chunks = explode('\\', $class);

        return array_pop($chunks);
    }

    /**
     * Perform the validation.
     *
     * @return void
     */
    abstract protected function doValidate();
}
