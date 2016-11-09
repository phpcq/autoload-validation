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

namespace PhpCodeQuality\AutoloadValidation\ClassLoader;

use PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException;
use PhpCodeQuality\AutoloadValidation\Exception\ClassNotFoundException;
use PhpCodeQuality\AutoloadValidation\Exception\DeprecatedClassException;
use PhpCodeQuality\AutoloadValidation\Exception\InvalidClassNameException;
use PhpCodeQuality\AutoloadValidation\Exception\ParentClassNotFoundException;

/**
 * This class is an enumerating class loader which tries all registered class loaders.
 *
 * It can be considered as an wrapper around spl_autoload_register() and the loaders within there.
 *
 * If offers the additional benefit to throw an \ParentClassNotFoundException() when a parent class could not be loaded.
 */
class EnumeratingClassLoader
{
    /**
     * The registered loaders.
     *
     * @var callable[]
     */
    private $loaders;

    /**
     * The name of the class currently being loaded.
     *
     * @var string
     */
    private $loading = null;

    /**
     * The list of previously registered loaders.
     *
     * @var callable[]
     */
    private $previousLoaders;

    /**
     * The list of loaded classes.
     *
     * @var string[]
     */
    private $loadList;

    /**
     * Loads the given class/interface/trait.
     *
     * @param string $class The name of the class to load.
     *
     * @return string The name of the loader that loaded the class.
     *
     * @throws ClassNotFoundException When a class could not be found.
     *
     * @throws ParentClassNotFoundException When a parent class could not be found.
     *
     * @throws DeprecatedClassException When a class raised a deprecation error.
     */
    public function loadClass($class)
    {
        $this->checkClassName($class);

        if (empty($this->loading)) {
            $this->loading = $class;
        }
        try {
            foreach ($this->loaders as $name => $loader) {
                try {
                    if ($loaderName = $this->tryLoad($loader, $class, $name)) {
                        return $loaderName;
                    }
                } catch (\ErrorException $exception) {
                    $this->loading = null;
                    if (E_USER_DEPRECATED === $exception->getSeverity()) {
                        throw new DeprecatedClassException($class, $exception->getMessage(), 0, $exception);
                    }
                    throw $exception;
                }
            }
        } catch (ParentClassNotFoundException $exception) {
            throw new ParentClassNotFoundException($class, 0, $exception);
        }
        if ($class !== $this->loading) {
            $this->loading = null;
            throw new ParentClassNotFoundException($class);
        }
        $this->loading = null;

        throw new ClassNotFoundException($class);
    }

    /**
     * Check if the given interface/class/trait is already loaded.
     *
     * @param string $className The full name of the class/interface/trait.
     *
     * @return bool
     */
    public static function isLoaded($className)
    {
        return (class_exists($className, false)
            || interface_exists($className, false)
            || (function_exists('trait_exists') && trait_exists($className, false)));
    }

    /**
     * Check if a class originates from the passed file.
     *
     * Returns true on success, the filename of the alternative file otherwise.
     *
     * @param string $className The class name.
     *
     * @param string $file      The file where the class should be contained.
     *
     * @return bool|string
     */
    public function isClassFromFile($className, $file)
    {
        $realFile = $this->getFileDeclaringClass($className);

        if ($file === $realFile) {
            return true;
        }

        return $realFile;
    }

    /**
     * Get the name of the file where the given class was declared.
     *
     * @param string $className The class name.
     *
     * @return string
     */
    public function getFileDeclaringClass($className)
    {
        if (!$this->isLoaded($className)) {
            return '';
        }

        $reflector = new \ReflectionClass($className);
        $realFile  = $reflector->getFileName();

        return $realFile;
    }

    /**
     * Register a class loader.
     *
     * @param callable $loader The closure to call.
     *
     * @param string   $name   The name of the loader.
     *
     * @return void
     */
    public function add($loader, $name = null)
    {
        if (empty($name)) {
            $name = 'loader.' . count($this->loaders);
        }

        $this->loaders[$name] = $loader;
    }

    /**
     * Register this instance as an auto loader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not.
     *
     * @return void
     */
    public function register($prepend = false)
    {
        // Just to make sure we have them loaded.
        spl_autoload_call('PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException');
        spl_autoload_call('PhpCodeQuality\AutoloadValidation\Exception\ClassNotFoundException');
        spl_autoload_call('PhpCodeQuality\AutoloadValidation\Exception\DeprecatedClassException');
        spl_autoload_call('PhpCodeQuality\AutoloadValidation\Exception\InvalidClassNameException');
        spl_autoload_call('PhpCodeQuality\AutoloadValidation\Exception\ParentClassNotFoundException');

        $this->previousLoaders = spl_autoload_functions();
        foreach ($this->previousLoaders as $previousLoader) {
            spl_autoload_unregister($previousLoader);
        }

        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregister this instance as an auto loader.
     *
     * @return void
     */
    public function unregister()
    {
        foreach (array_reverse($this->previousLoaders) as $previousLoader) {
            spl_autoload_register($previousLoader, true);
        }

        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Try to load the class.
     *
     * @param callable $loader     The loader to use.
     *
     * @param string   $class      The class name.
     *
     * @param string   $loaderName The internal name of the loader.
     *
     * @return null|string
     *
     * @throws ClassAlreadyRegisteredException When the class has been registered before.
     */
    private function tryLoad($loader, $class, $loaderName)
    {
        if (isset($this->loadList[$class])) {
            return $this->loadList[$class];
        }

        if ($this->isLoaded($class)) {
            throw new ClassAlreadyRegisteredException($class, $this->getFileDeclaringClass($class));
        }

        if (call_user_func($loader, $class)) {
            if ($class === $this->loading) {
                $this->loading = null;
            }

            return $this->loadList[$class] = $loaderName;
        }

        if ($this->isLoaded($class)) {
            trigger_error('loader ' . $loaderName . ' did not return true');
            return $this->loadList[$class] = $loaderName;
        }

        return null;
    }

    /**
     * Check the class name.
     *
     * @param string $class The class name.
     *
     * @return void
     *
     * @throws InvalidClassNameException When the class name is invalid.
     */
    private function checkClassName($class)
    {
        static $forbiddenClasses = array(
            '7.0' => array('tni', 'regetni', 'taolf', 'gnirts', 'loob', 'naeloob')
        );

        $match = strrev(strtolower($class));

        foreach ($forbiddenClasses as $since => $forbidden) {
            foreach ($forbidden as $forbiddenClass) {
                if (0 === strncmp($forbiddenClass, $match, strlen($forbiddenClass))) {
                    throw new InvalidClassNameException($class, $since);
                }
            }
        }
    }
}
