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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMap;
use PhpCodeQuality\AutoloadValidation\Violation\GenericViolation;
use PhpCodeQuality\AutoloadValidation\Violation\ClassAddedMoreThanOnceViolation;

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

        self::assertInstanceOf(AbstractValidator::class, $validator);
        self::assertInstanceOf(ClassMap::class, $validator->getClassMap());

        self::assertSame('name.validator-mock', $validator->getName());
    }

    /**
     * Test that the doValidate() is called only once.
     *
     * @return void
     */
    public function testDoValidateCalledOnlyOnce()
    {
        $validator = $this->getMockForAbstractClass(
            AbstractValidator::class,
            [
                'name.validator-mock',
                null,
                '/some/dir',
                $this->mockClassMapGenerator(),
                $this->mockReport()
            ]
        );

        $validator->expects(self::once())->method('doValidate');

        /** @var AbstractValidator $validator */
        $validator->validate();
        $validator->getClassMap();
    }

    /**
     * Data provider for testCutExtensionFromFileName
     *
     * @return array
     */
    public function providerCutExtensionFromFileName()
    {
        return [
            ['file', 'file.ext'],
            ['/some/path/file', '/some/path/file.ext'],
            ['/some/path/file.tar', '/some/path/file.tar.gz'],
        ];
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

        self::assertSame($expected, $validator->cutExtensionFromFileName($file));
    }

    /**
     * Data provider for testGetExtensionFromFileName
     *
     * @return array
     */
    public function providerGetExtensionFromFileName()
    {
        return [
            ['.ext', 'file.ext'],
            ['.ext', '/some/path/file.ext'],
            ['.gz', '/some/path/file.tar.gz'],
        ];
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

        self::assertSame($expected, $validator->getExtensionFromFileName($file));
    }

    /**
     * Data provider for testGetNameSpaceFromClassName
     *
     * @return array
     */
    public function providerGetNameSpaceFromClassName()
    {
        return [
            ['', '\StdClass'],
            ['Some\NameSpace', 'Some\NameSpace\ClassName'],
        ];
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

        self::assertSame($expected, $validator->getNameSpaceFromClassName($class));
    }

    /**
     * Data provider for testClassFromClassName
     *
     * @return array
     */
    public function providerGetClassFromClassName()
    {
        return [
            ['StdClass', '\StdClass'],
            ['ClassName', 'Some\NameSpace\ClassName'],
        ];
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

        self::assertSame($expected, $validator->getClassFromClassName($class));
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
        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects(self::once())
            ->method('scan')
            ->with('/some/dir/sub', null, 'Vendor\Namespace')
            ->willReturnCallback(function ($path, $whitelist, $namespace, &$messages) {
                $messages[] = 'This is a warning';
                return [];
            });

        $validator = new AbstractValidatorMock(
            'autoload.validator-mock',
            null,
            '/some/dir',
            $generator,
            $this->mockReport(GenericViolation::class)
        );

        self::assertEmpty($validator->classMapFromPath('/some/dir/sub', 'Vendor\Namespace'));
        self::assertTrue($validator->getClassMap()->isEmpty());
    }

    /**
     * Test that the generating of class maps from a path works.
     *
     * @return void
     */
    public function testClassMapGeneratingFromPathWorksWithNonEmptyResult()
    {
        $classMap = ['Vendor\Namespace\ClassName' => '/some/dir/sub/ClassName.php'];

        $generator = $this->mockClassMapGenerator($classMap);
        $generator
            ->expects(self::once())
            ->method('scan')
            ->with('/some/dir/sub', null, 'Vendor\Namespace');

        $validator = new AbstractValidatorMock(
            'autoload.validator-mock',
            null,
            '/some/dir',
            $generator,
            $this->mockReport()
        );

        self::assertEquals($classMap, $validator->classMapFromPath('/some/dir/sub', 'Vendor\Namespace'));
        $resultingClassMap = $validator->getClassMap();
        self::assertFalse($resultingClassMap->isEmpty());
        self::assertEquals($classMap, \iterator_to_array($resultingClassMap));
    }

    /**
     * Test that the generating of class maps from a path works.
     *
     * @return void
     */
    public function testClassMapGeneratingFromPathWorksWithNonEmptyResultAndLogsDuplicates()
    {
        $classMap = ['Vendor\Namespace\ClassName' => '/some/dir/sub2/ClassName.php'];

        $generator = $this->mockClassMapGenerator($classMap);
        $generator
            ->expects(self::once())
            ->method('scan')
            ->with('/some/dir/sub', null, 'Vendor\Namespace');

        $validator = new AbstractValidatorMock(
            'autoload.validator-mock',
            null,
            '/some/dir',
            $generator,
            $this->mockReport(
                ClassAddedMoreThanOnceViolation::class,
                [
                    'className'     => 'Vendor\Namespace\ClassName',
                    'files'         => ['/some/dir/sub1/ClassName.php', '/some/dir/sub2/ClassName.php'],
                    'validatorName' => 'autoload.validator-mock',
                ]
            )
        );

        $resultingClassMap = $validator->getClassMap();
        $resultingClassMap->add('Vendor\Namespace\ClassName', '/some/dir/sub1/ClassName.php');

        self::assertEquals($classMap, $validator->classMapFromPath('/some/dir/sub', 'Vendor\Namespace'));
        self::assertFalse($resultingClassMap->isEmpty());

        self::assertEquals(
            ['Vendor\Namespace\ClassName' => '/some/dir/sub1/ClassName.php'],
            \iterator_to_array($resultingClassMap)
        );
    }
}
