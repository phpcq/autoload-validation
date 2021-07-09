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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;
use PhpCodeQuality\AutoloadValidation\ClassLoader\EnumeratingClassLoader;
use PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException;
use PhpCodeQuality\AutoloadValidation\Exception\ClassNotFoundException;
use PhpCodeQuality\AutoloadValidation\Exception\DeprecatedClassException;
use PhpCodeQuality\AutoloadValidation\Exception\InvalidClassNameException;
use PhpCodeQuality\AutoloadValidation\Exception\ParentClassNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * This class tries to load all files
 */
class AllLoadingAutoLoader
{
    /**
     * The class map.
     *
     * @var string[]
     */
    private $classMap;

    /**
     * The class loader.
     *
     * @var EnumeratingClassLoader
     */
    private $loader;

    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Create a new instance.
     *
     * @param EnumeratingClassLoader $loader   The class loader.
     *
     * @param ClassMap               $classMap The class map.
     *
     * @param LoggerInterface        $logger   The logger.
     */
    public function __construct(EnumeratingClassLoader $loader, ClassMap $classMap, LoggerInterface $logger)
    {
        $this->loader   = $loader;
        $this->classMap = iterator_to_array($classMap);
        $this->logger   = $logger;
    }

    /**
     * Perform the load cycle.
     *
     * @return bool
     */
    public function run()
    {
        $this->loader->register();
        $result   = true;
        $classMap = array_slice($this->classMap, 0);

        // Now try to autoload all classes.
        while ($classMap) {
            $file  = reset($classMap);
            $class = key($classMap);
            try {
                $result = $this->tryLoadClass($class, $file) && $result;
            } catch (ParentClassNotFoundException $exception) {
                $this->handleParentClassNotFound($exception, $classMap);

                continue;
            }

            unset($classMap[$class]);
            $this->logger->info(
                'Loaded {class}.',
                array('class' => $class, 'file' => $file)
            );
        }

        $this->loader->unregister();

        return $result;
    }

    /**
     * Handle the parent class not found exceptions an log the parent class hierarchy.
     *
     * @param ParentClassNotFoundException $exception The exception to handle.
     *
     * @param array                        $classMap  The class map to manipulate.
     *
     * @return void
     */
    private function handleParentClassNotFound(ParentClassNotFoundException $exception, &$classMap)
    {
        // Now collect all parent classes and display them then.
        $classes = array();
        while ($exception) {
            $parentClass = $exception->getParentClass();
            $classes[]   = $parentClass;
            $exception   = $exception->getPrevious();

            unset($classMap[$parentClass]);
        }
        $classes     = array_reverse($classes);
        $parentClass = array_shift($classes);
        // Check if the parent class is known in the class map.
        if (isset($this->classMap[$parentClass])) {
            // It is known, nothing we can do anymore. Next one.
            $this->logger->error(
                'Could not load class.' . "\n" .
                'class:  {class}' . "\n" .
                'file:   {file}',
                array('class' => $parentClass, 'parent' => $this->classMap[$parentClass])
            );
        }
        $logLine = '{class0}<Missing parent class!>';
        $args    = array('class0' => $parentClass);
        // Ok, top most parent is somewhere lost, log this incident.
        foreach ($classes as $class) {
            $parName        = sprintf('class%1$d', count($args));
            $logLine       .= sprintf(' <= {%1$s}', $parName);
            $args[$parName] = $class;
        }

        $this->logger->warning($logLine, $args);
    }

    /**
     * Try to load a class.
     *
     * @param string $className The class name.
     *
     * @param string $file      The file where the class should be contained.
     *
     * @return bool
     */
    private function tryLoadClass($className, $file)
    {
        if ($this->isLoaded($className)) {
            return true;
        }
        $this->logger->debug(
            'Trying to load {class} (should be located in file {file}).',
            array('class' => $className, 'file' => $file)
        );

        try {
            $this->loader->loadClass($className);
            $this->checkDeclaringFile($className, $file);

            return true;
        } catch (DeprecatedClassException $exception) {
            $this->logger->warning(
                '{class} triggered deprecation: {message} (file {file}).',
                array('class' => $className, 'file' => $file, 'message' => $exception->getDeprecationMessage())
            );
            $this->checkDeclaringFile($className, $file);

            return true;
        } catch (ClassNotFoundException $exception) {
            $this->logger->error(
                'The autoloader could not load {class} (should be located in file {file}).',
                array('class' => $className, 'file' => $file)
            );
        } catch (ClassAlreadyRegisteredException $exception) {
            $this->logger->error(
                '{class} is already loaded from file {realfile}, can not test loading from file {file}.',
                array('class' => $className, 'file' => $file)
            );
        } catch (InvalidClassNameException $exception) {
            $this->logger->warning(
                'Skipped loading of {class}, it is a reserved name since {php-version} (file {file}).',
                array('class' => $className, 'file' => $file, 'php-version' => $exception->getPhpVersion())
            );
        } catch (\ErrorException $exception) {
            $this->logger->error(
                'Loading class {class}  failed with reason: {error}.',
                array('class' => $className, 'error' => $exception->getMessage())
            );
        }

        return false;
    }

    /**
     * Check if the given interface/class/trait is already loaded.
     *
     * @param string $className The full name of the class/interface/trait.
     *
     * @return bool
     */
    private function isLoaded($className)
    {
        return (class_exists($className, false)
            || interface_exists($className, false)
            || (function_exists('trait_exists') && trait_exists($className, false)));
    }

    /**
     * Check that the file declaring the class matches the one we expect.
     *
     * @param string $className The class name.
     *
     * @param string $file      The expected file.
     *
     * @return void
     */
    private function checkDeclaringFile($className, $file)
    {
        if (true !== ($realFile = $this->loader->isClassFromFile($className, $file))) {
            $this->logger->warning(
                '{class} was loaded from {realFile} (should be located in file {file}).',
                array(
                    'class'    => $className,
                    'file'     => $file,
                    'realFile' => $realFile
                )
            );
        }
    }
}
