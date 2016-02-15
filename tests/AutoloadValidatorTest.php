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

namespace PhpCodeQuality\AutoloadValidation\Test;

use PhpCodeQuality\AutoloadValidation\AutoloadValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ValidatorInterface;
use PhpCodeQuality\AutoloadValidation\Violation\ClassAddedMoreThanOnceViolation;

/**
 * This class tests the AutoloadValidator
 */
class AutoloadValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Retrieve a mock validator.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ValidatorInterface
     */
    private function getMockValidator()
    {
        return $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\AutoloadValidator\ValidatorInterface');
    }

    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $report = $this
            ->getMockBuilder('PhpCodeQuality\AutoloadValidation\Report\Report')
            ->disableOriginalConstructor()
            ->getMock();

        $validator = new AutoloadValidator(
            array(
                $this->getMockValidator(),
                $this->getMockValidator(),
                $this->getMockValidator(),
            ),
            $report
        );
        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator', $validator);
    }

    /**
     * Test that the class raises an exception when invalid validators are feeded.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage Invalid validator: DateTime
     */
    public function testCreationRaisesExceptionForInvalidValidators()
    {
        $report = $this
            ->getMockBuilder('PhpCodeQuality\AutoloadValidation\Report\Report')
            ->disableOriginalConstructor()
            ->getMock();

        new AutoloadValidator(
            array(
                $this->getMockValidator(),
                new \DateTime(),
                $this->getMockValidator(),
            ),
            $report
        );
    }

    /**
     * Test that validation calls all sub validators.
     *
     * @return void
     */
    public function testValidation()
    {
        $report = $this
            ->getMockBuilder('PhpCodeQuality\AutoloadValidation\Report\Report')
            ->disableOriginalConstructor()
            ->getMock();

        $validators = array(
            $this->getMockValidator(),
            $this->getMockValidator(),
            $this->getMockValidator(),
        );

        foreach ($validators as $validator) {
            $validator->expects($this->once())->method('validate');
        }

        $validator = new AutoloadValidator($validators, $report);
        $validator->validate();
    }

    /**
     * Test that getLoaders() calls all sub validators.
     *
     * @return void
     */
    public function testGetLoader()
    {
        $report = $this
            ->getMockBuilder('PhpCodeQuality\AutoloadValidation\Report\Report')
            ->disableOriginalConstructor()
            ->getMock();

        $validators = array(
            $this->getMockValidator(),
            $this->getMockValidator(),
            $this->getMockValidator(),
        );

        foreach ($validators as $key => $validator) {
            $validator->expects($this->once())->method('getLoader')->willReturn('autoload-fn.' . $key);
            $validator->method('getName')->willReturn('loader.' . $key);
        }

        $validator = new AutoloadValidator($validators, $report);

        $loaders = $validator->getLoaders();
        $this->assertEquals(array('loader.0', 'loader.1', 'loader.2'), array_keys($loaders));
        $this->assertEquals('autoload-fn.0', $loaders['loader.0']);
        $this->assertEquals('autoload-fn.1', $loaders['loader.1']);
        $this->assertEquals('autoload-fn.2', $loaders['loader.2']);
    }

    /**
     * Test that getLoader() calls all sub validators.
     *
     * @return void
     */
    public function testGetClassMap()
    {
        $that = $this;

        $report = $this
            ->getMockBuilder('PhpCodeQuality\AutoloadValidation\Report\Report')
            ->disableOriginalConstructor()
            ->getMock();
        $report
            ->expects($this->once())
            ->method('error')
            ->willReturnCallback(function (ClassAddedMoreThanOnceViolation $violation) use ($that) {
                $that->assertInstanceOf(
                    'PhpCodeQuality\AutoloadValidation\Violation\ClassAddedMoreThanOnceViolation',
                    $violation
                );
                $that->assertEquals(
                    array(
                        'validatorName' => 'idx0, dupe',
                        'className' => 'Vendor0\\Class0',
                        'files' => array(
                            'idx0' => '/src0/Class0.php',
                            'dupe' => '/different/source/Class0.php'
                        )
                    ),
                    $violation->getParameters()
                );
            });

        $validators = array(
            $this->getMockValidator(),
            $this->getMockValidator(),
            $this->getMockValidator(),
        );

        $shouldMap = array();
        foreach ($validators as $index => $validator) {
            $class = sprintf('Vendor%1$d\\Class%1$d', $index);
            $file  = sprintf('/src%1$d/Class%1$d.php', $index);

            $shouldMap[$class] = $file;

            $classMap = new AutoloadValidator\ClassMap();
            $classMap->add($class, $file);
            $validator->expects($this->once())->method('getName')->willReturn('idx' . $index);
            $validator->expects($this->once())->method('getClassMap')->willReturn($classMap);
        }

        $class = 'Vendor0\\Class0';
        $file  = '/different/source/Class0.php';

        $dupe    = $this->getMockValidator();
        $dupeMap = new AutoloadValidator\ClassMap();
        $dupeMap->add($class, $file);
        $dupe->expects($this->once())->method('getName')->willReturn('dupe');
        $dupe->expects($this->once())->method('getClassMap')->willReturn($dupeMap);
        $validators[] = $dupe;

        $validator = new AutoloadValidator($validators, $report);

        $classMap = $validator->getClassMap();

        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap', $classMap);
        foreach ($shouldMap as $class => $file) {
            $this->assertTrue($classMap->has($class));
            $this->assertEquals($file, $classMap->getFileFor($class));
        }
    }
}
