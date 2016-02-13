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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator;
use PhpCodeQuality\AutoloadValidation\Report\Report;

/**
 * This class tests the AbstractValidator.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractValidatorTest extends ValidatorTestCase
{
    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $validator = new AbstractValidatorMock(
            'name.validator-mock',
            null,
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $this->mockLogger();
        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator', $validator);
        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap',
            $validator->getClassMap()
        );

        $this->assertSame('name.validator-mock', $validator->getName());
    }

    /**
     * Test that the doValidate() is called only once.
     *
     * @return void
     */
    public function testDoValidateCalledOnlyOnce()
    {
        $validator = $this->getMockForAbstractClass(
            'PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator',
            array(
                'name.validator-mock',
                null,
                '/some/dir',
                $this->mockClassMapGenerator(),
                $this->mockReport()
            )
        );
        $validator->logger = $this->mockLogger();

        $validator->expects($this->once())->method('doValidate');

        /** @var AbstractValidator $validator */

        $validator->validate();
        $validator->getClassMap();
    }

    /**
     * Test that the message logging works.
     *
     * @return void
     */
    public function testMessageLoggingWorks()
    {
        $logger = $this->mockLogger();

        $logger
            ->expects($this->once())
            ->method('error')
            ->with('{name} error {value}', array('value' => 'text', 'name' => 'autoload.validator-mock'));
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('{name} warning {value}', array('value' => 'text', 'name' => 'autoload.validator-mock'));
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('{name} info {value}', array('value' => 'text', 'name' => 'autoload.validator-mock'));

        $report = new Report(array());

        $validator = new AbstractValidatorMock(
            'autoload.validator-mock',
            null,
            '/some/dir',
            $this->mockClassMapGenerator(),
            $report
        );
        $validator->logger = $logger;

        $validator->error('{name} error {value}', array('value' => 'text'));
        $validator->warning('{name} warning {value}', array('value' => 'text'));
        $validator->info('{name} info {value}', array('value' => 'text'));

        $validator->validate();

        $this->assertTrue($report->hasError());
    }

    /**
     * Data provider for testCutExtensionFromFileName
     *
     * @return array
     */
    public function providerCutExtensionFromFileName()
    {
        return array(
            array('file', 'file.ext'),
            array('/some/path/file', '/some/path/file.ext'),
            array('/some/path/file.tar', '/some/path/file.tar.gz'),
        );
    }

    /**
     * Test that the method cutExtensionFromFileName works.
     *
     * @param string $expected The expected value.
     *
     * @param string $file     The file name.
     *
     * @return void
     *
     * @dataProvider providerCutExtensionFromFileName
     */
    public function testCutExtensionFromFileName($expected, $file)
    {
        $validator = new AbstractValidatorMock(
            'dummy',
            null,
            '/',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $this->mockLogger();

        $this->assertSame($expected, $validator->cutExtensionFromFileName($file));
    }

    /**
     * Data provider for testGetExtensionFromFileName
     *
     * @return array
     */
    public function providerGetExtensionFromFileName()
    {
        return array(
            array('.ext', 'file.ext'),
            array('.ext', '/some/path/file.ext'),
            array('.gz', '/some/path/file.tar.gz'),
        );
    }

    /**
     * Test that the method getExtensionFromFileName works.
     *
     * @param string $expected The expected value.
     *
     * @param string $file     The file name.
     *
     * @return void
     *
     * @dataProvider providerGetExtensionFromFileName
     */
    public function testGetExtensionFromFileName($expected, $file)
    {
        $validator = new AbstractValidatorMock(
            'dummy',
            null,
            '/',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $this->mockLogger();

        $this->assertSame($expected, $validator->getExtensionFromFileName($file));
    }

    /**
     * Data provider for testGetNameSpaceFromClassName
     *
     * @return array
     */
    public function providerGetNameSpaceFromClassName()
    {
        return array(
            array('', '\StdClass'),
            array('Some\NameSpace', 'Some\NameSpace\ClassName'),
        );
    }

    /**
     * Test that the method getNameSpaceFromClassName works.
     *
     * @param string $expected The expected value.
     *
     * @param string $class    The class name.
     *
     * @return void
     *
     * @dataProvider providerGetNameSpaceFromClassName
     */
    public function testGetNameSpaceFromClassName($expected, $class)
    {
        $validator = new AbstractValidatorMock(
            'dummy',
            null,
            '/',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $this->mockLogger();

        $this->assertSame($expected, $validator->getNameSpaceFromClassName($class));
    }

    /**
     * Data provider for testClassFromClassName
     *
     * @return array
     */
    public function providerGetClassFromClassName()
    {
        return array(
            array('StdClass', '\StdClass'),
            array('ClassName', 'Some\NameSpace\ClassName'),
        );
    }

    /**
     * Test that the method getNameSpaceFromClassName works.
     *
     * @param string $expected The expected value.
     *
     * @param string $class    The class name.
     *
     * @return void
     *
     * @dataProvider providerGetClassFromClassName
     */
    public function testGetClassFromClassName($expected, $class)
    {
        $validator = new AbstractValidatorMock(
            'dummy',
            null,
            '/',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $this->mockLogger();

        $this->assertSame($expected, $validator->getClassFromClassName($class));
    }

    /**
     * Test that the generating of class maps from a path works.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testClassMapGeneratingFromPathWorksWithEmptyResult()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('warning');
        $logger->expects($this->never())->method('info');

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->once())
            ->method('scan')
            ->with('/some/dir/sub', null, 'Vendor\Namespace')
            ->willReturnCallback(function ($path, $whitelist, $namespace, &$messages) {
                $messages[] = 'This is a warning';
                return array();
            });

        $validator = new AbstractValidatorMock(
            'autoload.validator-mock',
            null,
            '/some/dir',
            $generator,
            $this->mockReport('PhpCodeQuality\AutoloadValidation\Violation\GenericViolation')
        );
        $validator->logger = $logger;

        $this->assertEmpty($validator->classMapFromPath('/some/dir/sub', 'Vendor\Namespace'));
        $this->assertTrue($validator->getClassMap()->isEmpty());
    }

    /**
     * Test that the generating of class maps from a path works.
     *
     * @return void
     */
    public function testClassMapGeneratingFromPathWorksWithNonEmptyResult()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('warning');
        $logger->expects($this->never())->method('info');

        $classMap = array('Vendor\Namespace\ClassName' => '/some/dir/sub/ClassName.php');

        $generator = $this->mockClassMapGenerator($classMap);
        $generator
            ->expects($this->once())
            ->method('scan')
            ->with('/some/dir/sub', null, 'Vendor\Namespace');

        $validator = new AbstractValidatorMock(
            'autoload.validator-mock',
            null,
            '/some/dir',
            $generator,
            $this->mockReport()
        );
        $validator->logger = $logger;

        $this->assertEquals($classMap, $validator->classMapFromPath('/some/dir/sub', 'Vendor\Namespace'));
        $resultingClassMap = $validator->getClassMap();
        $this->assertFalse($resultingClassMap->isEmpty());
        $this->assertEquals($classMap, iterator_to_array($resultingClassMap));
    }

    /**
     * Test that the generating of class maps from a path works.
     *
     * @return void
     */
    public function testClassMapGeneratingFromPathWorksWithNonEmptyResultAndLogsDuplicates()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('warning');
        $logger->expects($this->never())->method('info');

        $classMap = array('Vendor\Namespace\ClassName' => '/some/dir/sub2/ClassName.php');

        $generator = $this->mockClassMapGenerator($classMap);
        $generator
            ->expects($this->once())
            ->method('scan')
            ->with('/some/dir/sub', null, 'Vendor\Namespace');

        $validator = new AbstractValidatorMock(
            'autoload.validator-mock',
            null,
            '/some/dir',
            $generator,
            $this->mockReport(
                'PhpCodeQuality\AutoloadValidation\Violation\ClassAddedMoreThanOnceViolation',
                array(
                    'className'     => 'Vendor\Namespace\ClassName',
                    'files'         => array('/some/dir/sub1/ClassName.php', '/some/dir/sub2/ClassName.php'),
                    'validatorName' => 'autoload.validator-mock',
                )
            )
        );
        $validator->logger = $logger;

        $resultingClassMap = $validator->getClassMap();
        $resultingClassMap->add('Vendor\Namespace\ClassName', '/some/dir/sub1/ClassName.php');

        $this->assertEquals($classMap, $validator->classMapFromPath('/some/dir/sub', 'Vendor\Namespace'));
        $this->assertFalse($resultingClassMap->isEmpty());

        $this->assertEquals(
            array('Vendor\Namespace\ClassName' => '/some/dir/sub1/ClassName.php'),
            iterator_to_array($resultingClassMap)
        );
    }
}
