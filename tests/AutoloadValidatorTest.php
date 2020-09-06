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

namespace PhpCodeQuality\AutoloadValidation\Test;

use PhpCodeQuality\AutoloadValidation\AutoloadValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ValidatorInterface;
use PhpCodeQuality\AutoloadValidation\Violation\ClassAddedMoreThanOnceViolation;
use PHPUnit\Framework\TestCase;
use PhpCodeQuality\AutoloadValidation\Report\Report;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;

/**
 * This class tests the AutoloadValidator
 *
 * @covers \PhpCodeQuality\AutoloadValidation\AutoloadValidator
 */
class AutoloadValidatorTest extends TestCase
{
    /**
     * Retrieve a mock validator.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ValidatorInterface
     */
    private function getMockValidator()
    {
        return $this->getMockForAbstractClass(ValidatorInterface::class);
    }

    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $report = $this->getMockBuilder(Report::class)->disableOriginalConstructor()->getMock();

        $validator = new AutoloadValidator(
            [
                $this->getMockValidator(),
                $this->getMockValidator(),
                $this->getMockValidator(),
            ],
            $report
        );
        self::assertInstanceOf(AutoloadValidator::class, $validator);
    }

    /**
     * Test that the class raises an exception when invalid validators are feeded.
     *
     * @return void
     */
    public function testCreationRaisesExceptionForInvalidValidators()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }

        $report = $this->getMockBuilder(Report::class)->disableOriginalConstructor()->getMock();

        new AutoloadValidator(
            [
                $this->getMockValidator(),
                new \DateTime(),
                $this->getMockValidator(),
            ],
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
        $report = $this->getMockBuilder(Report::class)->disableOriginalConstructor()->getMock();

        $validators = [
            $this->getMockValidator(),
            $this->getMockValidator(),
            $this->getMockValidator(),
        ];

        foreach ($validators as $validator) {
            $validator->expects(self::once())->method('validate');
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
        $report = $this->getMockBuilder(Report::class)->disableOriginalConstructor()->getMock();

        $validators = [
            $this->getMockValidator(),
            $this->getMockValidator(),
            $this->getMockValidator(),
        ];

        foreach ($validators as $key => $validator) {
            $validator->expects(self::once())->method('getLoader')->willReturn('autoload-fn.' . $key);
            $validator->method('getName')->willReturn('loader.' . $key);
        }

        $validator = new AutoloadValidator($validators, $report);

        $loaders = $validator->getLoaders();
        self::assertEquals(['loader.0', 'loader.1', 'loader.2'], \array_keys($loaders));
        self::assertEquals('autoload-fn.0', $loaders['loader.0']);
        self::assertEquals('autoload-fn.1', $loaders['loader.1']);
        self::assertEquals('autoload-fn.2', $loaders['loader.2']);
    }

    /**
     * Test that getLoader() calls all sub validators.
     *
     * @return void
     */
    public function testGetClassMap()
    {
        $that = $this;

        $report = $this->getMockBuilder(Report::class)->disableOriginalConstructor()->getMock();
        $report
            ->expects(self::once())
            ->method('error')
            ->willReturnCallback(function (ClassAddedMoreThanOnceViolation $violation) use ($that) {
                $that->assertInstanceOf(ClassAddedMoreThanOnceViolation::class, $violation);
                $that->assertEquals(
                    [
                        'validatorName' => 'idx0, dupe',
                        'className' => 'Vendor0\\Class0',
                        'files' => [
                            'idx0' => '/src0/Class0.php',
                            'dupe' => '/different/source/Class0.php'
                        ]
                    ],
                    $violation->getParameters()
                );
            });

        $validators = [
            $this->getMockValidator(),
            $this->getMockValidator(),
            $this->getMockValidator(),
        ];

        $shouldMap = array();
        foreach ($validators as $index => $validator) {
            $class = \sprintf('Vendor%1$d\\Class%1$d', $index);
            $file  = \sprintf('/src%1$d/Class%1$d.php', $index);

            $shouldMap[$class] = $file;

            $classMap = new AutoloadValidator\ClassMap();
            $classMap->add($class, $file);
            $validator->expects(self::once())->method('getName')->willReturn('idx' . $index);
            $validator->expects(self::once())->method('getClassMap')->willReturn($classMap);
        }

        $class = 'Vendor0\\Class0';
        $file  = '/different/source/Class0.php';

        $dupe    = $this->getMockValidator();
        $dupeMap = new AutoloadValidator\ClassMap();
        $dupeMap->add($class, $file);
        $dupe->expects(self::once())->method('getName')->willReturn('dupe');
        $dupe->expects(self::once())->method('getClassMap')->willReturn($dupeMap);
        $validators[] = $dupe;

        $validator = new AutoloadValidator($validators, $report);

        $classMap = $validator->getClassMap();

        self::assertInstanceOf(ClassMap::class, $classMap);
        foreach ($shouldMap as $class => $file) {
            self::assertTrue($classMap->has($class));
            self::assertEquals($file, $classMap->getFileFor($class));
        }
    }
}
