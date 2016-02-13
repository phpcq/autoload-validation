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

use Composer\Autoload\ClassLoader;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMapValidator;

/**
 * This class tests the ClassMapValidator.
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
        $validator->logger = $this->mockLogger();
        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator', $validator);
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
        $validator->logger = $this->mockLogger();

        $loader = new ClassLoader();

        $validator->addToLoader($loader);

        $this->assertEquals(
            array(
                'Vendor\Namespace\ClassName1' => '/some/dir/src/ClassName1.php',
                'Vendor\Namespace\ClassName2' => '/some/dir/another/dir/ClassName.php'
            ),
            $loader->getClassMap()
        );
    }

    /**
     * Test that an error is reported when no classes have been found.
     *
     * @return void
     */
    public function testScanAddsErrorWhenNothingFound()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->once())->method('error')->with(
            ClassMapValidator::ERROR_CLASSMAP_NO_CLASSES_FOUND_FOR_PREFIX,
            array(
                'prefix'  => '/some/dir/src',
                'name'   => 'autoload.classmap',
            )
        );

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->once())
            ->method('scan')
            ->with('/some/dir/src', null)
            ->willReturn(array());

        $validator = new ClassMapValidator(
            'autoload.classmap',
            array('/src'),
            '/some/dir',
            $generator,
            $this->mockReport()
        );
        $validator->logger = $logger;

        $validator->validate();
    }

    /**
     * Test that all directories get scanned.
     *
     * @return void
     */
    public function testScansAllSubDirs()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->never())->method('error');

        $generator = $this->mockClassMapGenerator();
        $generator->method('scan')
            ->withConsecutive(
                array('/some/dir/src'),
                array('/some/dir/another/dir')
            )
            ->willReturnOnConsecutiveCalls(
                array('Vendor\Namespace\ClassName1' => '/some/dir/src/ClassName1.php'),
                array('Vendor\Namespace\ClassName2' => '/some/dir/another/dir/ClassName.php')
            );

        $validator = new ClassMapValidator(
            'autoload.classmap',
            array(
                '/src',
                '/another/dir'
            ),
            '/some/dir',
            $generator,
            $this->mockReport()
        );
        $validator->logger = $logger;

        $validator->validate();

        $resultingClassMap = $validator->getClassMap();

        $this->assertEquals(
            array(
                'Vendor\Namespace\ClassName1' => '/some/dir/src/ClassName1.php',
                'Vendor\Namespace\ClassName2' => '/some/dir/another/dir/ClassName.php'
            ),
            iterator_to_array($resultingClassMap)
        );
    }
}
