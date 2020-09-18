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

use PDepend\TextUI\Runner;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AutoloadValidatorFactory;
use PhpCodeQuality\AutoloadValidation\Report\Report;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMapValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\FilesValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr0Validator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr4Validator;

/**
 * This class tests the AutoloadValidatorFactory.
 *
 * @covers \PhpCodeQuality\AutoloadValidation\AutoloadValidator\AutoloadValidatorFactory
 */
class AutoloadValidatorFactoryTest extends ValidatorTestCase
{
    /**
     * Mock the factory.
     *
     * @return AutoloadValidatorFactory
     */
    public function mockFactory()
    {
        $factory = new AutoloadValidatorFactory(
            '/some/dir',
            $this->mockClassMapGenerator(),
            new Report([])
        );

        self::assertInstanceOf(AutoloadValidatorFactory::class, $factory);

        return $factory;
    }

    /**
     * Test that the factory creates a ClassMapValidator.
     *
     * @return void
     */
    public function testClassMapValidatorCreation()
    {
        $factory = $this->mockFactory();

        self::assertInstanceOf(ClassMapValidator::class, $factory->createValidator('autoload', 'classmap', []));
    }

    /**
     * Test that the factory creates a FilesValidator.
     *
     * @return void
     */
    public function testFilesValidatorCreation()
    {
        $factory = $this->mockFactory();

        self::assertInstanceOf(FilesValidator::class, $factory->createValidator('autoload', 'files', []));
    }

    /**
     * Test that the factory creates a Psr0Validator.
     *
     * @return void
     */
    public function testPsr0ValidatorCreation()
    {
        $factory = $this->mockFactory();

        self::assertInstanceOf(Psr0Validator::class, $factory->createValidator('autoload', 'psr-0', []));
    }

    /**
     * Test that the factory creates a Psr4Validator.
     *
     * @return void
     */
    public function testPsr4ValidatorCreation()
    {
        $factory = $this->mockFactory();

        self::assertInstanceOf(Psr4Validator::class, $factory->createValidator('autoload', 'psr-4', []));
    }

    /**
     * Test that the factory creates a Psr4Validator.
     *
     * @return void
     */
    public function testExcludeFromClassmapSkippedInCreation()
    {
        self::assertEmpty($this->mockFactory()->createValidator('autoload', 'exclude-from-classmap', []));
    }

    /**
     * Test that the factory throws an exception for unknown type names.
     *
     * @return void
     */
    public function testFactoryThrowsExceptionForUnknownType()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }

        $factory = $this->mockFactory();

        $factory->createValidator('autoload', '----unkown-----type---name----', []);
    }

    /**
     * Test that the factory returns an empty array when the composer.json data has no auto loader sections.
     *
     * @return void
     */
    public function testFactoryReturnsEmptyWhenNoSectionsGiven()
    {
        $factory = $this->mockFactory();

        self::assertEquals([], $factory->createFromComposerJson([]));
    }

    /**
     * Test that the factory returns an array containing validators from both sections.
     *
     * @return void
     */
    public function testFactoryReturnsValidatorsFromBothSections()
    {
        $factory = $this->mockFactory();

        $validators = $factory->createFromComposerJson(
            [
                'autoload' => [
                    'psr-0' => ['Vendor\\' => 'src']
                ],
                'autoload-dev' => [
                    'psr-4' => ['Vendor\\' => 'src']
                ],
            ]
        );

        self::assertCount(2, $validators);
    }
}
