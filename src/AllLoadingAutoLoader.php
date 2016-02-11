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
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;
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
     * @var ClassLoader
     */
    private $loader;

    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The autoload function hack.
     *
     * This closure will throw an \ParentClassNotFoundException when invoked, so we will not get a fatal error.
     *
     * @var \Closure
     */
    private $parentClassNotFoundHack;

    /**
     * Create a new instance.
     *
     * @param ClassLoader     $loader   The class loader.
     *
     * @param ClassMap        $classMap The class map.
     *
     * @param LoggerInterface $logger   The logger.
     */
    public function __construct(ClassLoader $loader, ClassMap $classMap, LoggerInterface $logger)
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
        $this->registerFallback();
        $result = true;

        // Now try to autoload all classes.
        foreach ($this->classMap as $class => $file) {
            $result = $this->tryLoadClass($class, $file) && $result;
            $this->logger->info(
                'Loaded {class}.',
                array('class' => $class, 'file' => $file)
            );
        }

        $this->unRegisterFallback();

        return $result;
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
            if (!$this->loader->loadClass($className)) {
                $this->logger->error(
                    'The autoloader could not load {class} (should be located in file {file}).',
                    array('class' => $className, 'file' => $file)
                );

                return false;
            }

            return true;
        } catch (ParentClassNotFoundException $exception) {
            $this->logger->notice(
                'Loading class {class} incomplete due to missing parent class: {parent}.',
                array('class' => $className, 'parent' => $exception->getParentClass())
            );

            // We consider this non fatal.
            return true;
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
     * Register the class loader hack.
     *
     * @return void
     *
     * @throws ParentClassNotFoundException In the auto loader closure being registered.
     */
    private function registerFallback()
    {
        $that = $this;

        $this->parentClassNotFoundHack = function ($class) use ($that) {
            if (isset($that->classMap[$class]) && $that->tryLoadClass($class, $that->classMap[$class])) {
                if ($that->isLoaded($class)) {
                    return true;
                }
            }

            throw new ParentClassNotFoundException($class);
        };

        // Important! Add to the end of auto loaders, do NOT prepend.
        spl_autoload_register($this->parentClassNotFoundHack);
    }

    /**
     * Unregister the class loader hack.
     *
     * @return void
     */
    private function unRegisterFallback()
    {
        spl_autoload_unregister($this->parentClassNotFoundHack);

        unset($this->parentClassNotFoundHack);
    }
}
