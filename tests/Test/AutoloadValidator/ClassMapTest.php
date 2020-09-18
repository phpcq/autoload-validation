<?php

/**
 * This file is part of phpcq/autoload-validation.
 *
 * (c) 2014-2020 Christian Schiffler, Tristan Lins
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    phpcq/autoload-validation
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2014-2020 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/phpcq/autoload-validation/blob/master/LICENSE MIT
 * @link       https://github.com/phpcq/autoload-validation
 * @filesource
 */

namespace PhpCodeQuality\AutoloadValidation\Test\AutoloadValidator;

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;
use PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException;
use PHPUnit\Framework\TestCase;

/**
 * This class tests the ClassMap.
 *
 * @covers \PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap
 */
class ClassMapTest extends TestCase
{
    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $classMap = new ClassMap();
        self::assertInstanceOf(ClassMap::class, $classMap);
        self::assertTrue($classMap->isEmpty());
    }

    /**
     * Test that the class is traversable.
     *
     * @return void
     */
    public function testClassMapIsTraversable()
    {
        $classMap = new ClassMap();
        self::assertInstanceOf(ClassMap::class, $classMap);
        self::assertInstanceOf(\Traversable::class, $classMap);
        self::assertInstanceOf(\Traversable::class, $classMap->getIterator());
    }

    /**
     * Test that the class map can be manipulated.
     *
     * @return void
     */
    public function testClassMapCanAddClasses()
    {
        $classMap = new ClassMap();

        self::assertFalse($classMap->has('Some\Class'));
        self::assertSame($classMap, $classMap->add('Some\Class', '/some/path'));
        self::assertTrue($classMap->has('Some\Class'));
        self::assertSame('/some/path', $classMap->getFileFor('Some\Class'));
        self::assertFalse($classMap->isEmpty());
    }

    /**
     * Test that the class map throws an exception when a class has already been added.
     *
     * @return void
     */
    public function testClassMapThrowsExceptionForAddingClassTwice()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(ClassAlreadyRegisteredException::class);
        } else {
            $this->setExpectedException(ClassAlreadyRegisteredException::class);
        }

        $classMap = new ClassMap();
        $classMap->add('Some\Class', '/some/path');
        $classMap->add('Some\Class', '/other/path');
    }

    /**
     * Test that the class map does not throw an exception when a class has already been added from the same file.
     *
     * @return void
     */
    public function testClassMapDoesNotThrowExceptionForAddingClassTwiceFromSameFile()
    {
        $classMap = new ClassMap();
        $classMap->add('Some\Class', '/some/path');
        $classMap->add('Some\Class', '/some/path');
        $this->addToAssertionCount(1);
    }

    /**
     * Test that class names are normalized.
     *
     * @return void
     */
    public function testClassMapNormalizesClassNames()
    {
        $classMap = new ClassMap();
        $classMap->add('\Some\Class', '/some/path');
        self::assertTrue($classMap->has('Some\Class'));
    }

    /**
     * Test that the class map can be manipulated.
     *
     * @return void
     */
    public function testClassMapCanRemoveClasses()
    {
        $classMap = new ClassMap();
        $classMap->add('Some\Class', '/some/path');

        self::assertSame($classMap, $classMap->remove('Some\Class'));
        self::assertFalse($classMap->has('Some\Class'));
        self::assertTrue($classMap->isEmpty());
    }

    /**
     * Test that the class map throws an exception when an unregistered class shall get removed.
     *
     * @return void
     */
    public function testClassMapThrowsExceptionForRemovingUnregistered()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }

        $classMap = new ClassMap();
        $classMap->remove('Some\Class');
    }

    /**
     * Test that the class map throws an exception when a class file is retrieved for an unregistered class.
     *
     * @return void
     */
    public function testClassMapThrowsExceptionGettingFileOfUnregisteredClass()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }

        $classMap = new ClassMap();

        $classMap->getFileFor('Unknown\Class');
    }
}
