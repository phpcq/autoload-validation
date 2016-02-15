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

namespace PhpCodeQuality\AutoloadValidation\Test\AutoloadValidator;

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;

/**
 * This class tests the ClassMap.
 */
class ClassMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $classMap = new ClassMap();
        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap', $classMap);
        $this->assertTrue($classMap->isEmpty());
    }

    /**
     * Test that the class is traversable.
     *
     * @return void
     */
    public function testClassMapIsTraversable()
    {
        $classMap = new ClassMap();
        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap', $classMap);
        $this->assertInstanceOf('\Traversable', $classMap);
        $this->assertInstanceOf('\Traversable', $classMap->getIterator());
    }

    /**
     * Test that the class map can be manipulated.
     *
     * @return void
     */
    public function testClassMapCanAddClasses()
    {
        $classMap = new ClassMap();

        $this->assertFalse($classMap->has('Some\Class'));
        $this->assertSame($classMap, $classMap->add('Some\Class', '/some/path'));
        $this->assertTrue($classMap->has('Some\Class'));
        $this->assertSame('/some/path', $classMap->getFileFor('Some\Class'));
        $this->assertFalse($classMap->isEmpty());
    }

    /**
     * Test that the class map throws an exception when a class has already been added.
     *
     * @return void
     *
     * @expectedException \PhpCodeQuality\AutoloadValidation\Exception\ClassAlreadyRegisteredException
     */
    public function testClassMapThrowsExceptionForAddingClassTwice()
    {
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
        $this->assertTrue($classMap->has('Some\Class'));
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

        $this->assertSame($classMap, $classMap->remove('Some\Class'));
        $this->assertFalse($classMap->has('Some\Class'));
        $this->assertTrue($classMap->isEmpty());
    }

    /**
     * Test that the class map throws an exception when an unregistered class shall get removed.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testClassMapThrowsExceptionForRemovingUnregistered()
    {
        $classMap = new ClassMap();
        $classMap->remove('Some\Class');
    }

    /**
     * Test that the class map throws an exception when a class file is retrieved for an unregistered class.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testClassMapThrowsExceptionGettingFileOfUnregisteredClass()
    {
        $classMap = new ClassMap();

        $classMap->getFileFor('Unknown\Class');
    }
}
