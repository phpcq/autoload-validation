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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMapValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator;
use Composer\Autoload\ClassLoader;
use PhpCodeQuality\AutoloadValidation\Violation\ClassMap\NoClassesFoundInPathViolation;

/**
 * This class tests the ClassMapValidator.
 *
 * @covers \PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMapValidator
 */
class ClassMapValidatorTest extends ValidatorTestCase
{
    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $validator = new ClassMapValidator(
            'autoload.classmap',
            array('/src'),
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        self::assertInstanceOf(AbstractValidator::class, $validator);
    }

    /**
     * Test that the validator adds the classes to the loader.
     *
     * @return void
     */
    public function testAddToLoader()
    {
        $validator = new ClassMapValidator(
            'autoload.classmap',
            array('/src'),
            '/some/dir',
            $this->mockClassMapGenerator(
                array(
                    'Vendor\Namespace\ClassName1' => '/some/dir/src/ClassName1.php',
                    'Vendor\Namespace\ClassName2' => '/some/dir/another/dir/ClassName.php'
                )
            ),
            $this->mockReport()
        );


        $loader = $validator->getLoader();
        self::assertInstanceOf(ClassLoader::class, $loader[0]);
        self::assertEquals(
            [
                'Vendor\Namespace\ClassName1' => '/some/dir/src/ClassName1.php',
                'Vendor\Namespace\ClassName2' => '/some/dir/another/dir/ClassName.php'
            ],
            $loader[0]->getClassMap()
        );
    }

    /**
     * Test that an error is reported when no classes have been found.
     *
     * @return void
     */
    public function testScanAddsErrorWhenNothingFound()
    {
        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects(self::once())
            ->method('scan')
            ->with('/some/dir/src', null)
            ->willReturn([]);

        $validator = new ClassMapValidator(
            'autoload.classmap',
            ['/src'],
            '/some/dir',
            $generator,
            $this->mockReport(
                NoClassesFoundInPathViolation::class,
                [
                    'classMapPrefix' => '/src',
                    'validatorName'  => 'autoload.classmap'
                ]
            )
        );

        $validator->validate();
    }

    /**
     * Test that all directories get scanned.
     *
     * @return void
     */
    public function testScansAllSubDirs()
    {
        $generator = $this->mockClassMapGenerator();
        $generator->method('scan')
            ->withConsecutive(
                ['/some/dir/src'],
                ['/some/dir/another/dir']
            )
            ->willReturnOnConsecutiveCalls(
                ['Vendor\Namespace\ClassName1' => '/some/dir/src/ClassName1.php'],
                ['Vendor\Namespace\ClassName2' => '/some/dir/another/dir/ClassName.php']
            );

        $validator = new ClassMapValidator(
            'autoload.classmap',
            [
                '/src',
                '/another/dir'
            ],
            '/some/dir',
            $generator,
            $this->mockReport()
        );

        $validator->validate();

        $resultingClassMap = $validator->getClassMap();

        self::assertEquals(
            [
                'Vendor\Namespace\ClassName1' => '/some/dir/src/ClassName1.php',
                'Vendor\Namespace\ClassName2' => '/some/dir/another/dir/ClassName.php'
            ],
            \iterator_to_array($resultingClassMap)
        );
    }
}
