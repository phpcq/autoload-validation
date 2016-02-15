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

use PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException;

/**
 * This class holds the class map abstraction.
 */
class ClassMap implements \IteratorAggregate
{
    /**
     * The list of registered classes.
     *
     * @var string[]
     */
    private $classes = array();

    /**
     * Check if the class map is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->classes);
    }

    /**
     * Check if the class has been registered.
     *
     * @param string $class The class to search.
     *
     * @return bool
     */
    public function has($class)
    {
        return array_key_exists($this->normalizeClassName($class), $this->classes);
    }

    /**
     * Add a class entry.
     *
     * @param string $class The class name.
     *
     * @param string $file  The file containing the class.
     *
     * @return ClassMap
     *
     * @throws ClassAlreadyRegisteredException When the class has already been registered.
     */
    public function add($class, $file)
    {
        $class = $this->normalizeClassName($class);

        if ($this->has($class)) {
            if (($localFile = $this->getFileFor($class)) !== $file) {
                throw new ClassAlreadyRegisteredException($class, $localFile);
            }
        }

        $this->classes[$class] = $file;

        return $this;
    }

    /**
     * Remove a class from the map.
     *
     * @param string $class The name of the class to remove.
     *
     * @return ClassMap
     *
     * @throws \InvalidArgumentException When the passed class has not been registered.
     */
    public function remove($class)
    {
        $class = $this->normalizeClassName($class);

        if (!$this->has($class)) {
            throw new \InvalidArgumentException('Class ' . $class . ' is not registered.');
        }

        unset($this->classes[$class]);

        return $this;
    }

    /**
     * Obtain the file the class resides in.
     *
     * @param string $class The class name.
     *
     * @return string
     *
     * @throws \InvalidArgumentException When the passed class has not been registered.
     */
    public function getFileFor($class)
    {
        $class = $this->normalizeClassName($class);

        if (!$this->has($class)) {
            throw new \InvalidArgumentException('Class ' . $class . ' is not registered.');
        }

        return $this->classes[$class];
    }

    /**
     * Retrieve the iterator.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->classes);
    }

    /**
     * Normalize a class name (cut leading backslash).
     *
     * @param string $className The class name to normalize.
     *
     * @return string
     */
    private function normalizeClassName($className)
    {
        if ('\\' === $className[0]) {
            return substr($className, 1);
        }

        return $className;
    }
}
