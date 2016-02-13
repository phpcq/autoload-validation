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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AutoloadValidatorFactory;
use PhpCodeQuality\AutoloadValidation\Report\Report;

/**
 * This class tests the AutoloadValidatorFactory.
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
            new Report(array())
        );
        $factory->logger = $this->mockLogger();

        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\AutoloadValidator\AutoloadValidatorFactory',
            $factory
        );

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

        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\AutoloadValidator\ClassMapValidator',
            $factory->createValidator('autoload', 'classmap', array())
        );
    }

    /**
     * Test that the factory creates a FilesValidator.
     *
     * @return void
     */
    public function testFilesValidatorCreation()
    {
        $factory = $this->mockFactory();

        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\AutoloadValidator\FilesValidator',
            $factory->createValidator('autoload', 'files', array())
        );
    }

    /**
     * Test that the factory creates a Psr0Validator.
     *
     * @return void
     */
    public function testPsr0ValidatorCreation()
    {
        $factory = $this->mockFactory();

        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr0Validator',
            $factory->createValidator('autoload', 'psr-0', array())
        );
    }

    /**
     * Test that the factory creates a Psr4Validator.
     *
     * @return void
     */
    public function testPsr4ValidatorCreation()
    {
        $factory = $this->mockFactory();

        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr4Validator',
            $factory->createValidator('autoload', 'psr-4', array())
        );
    }

    /**
     * Test that the factory throws an exception for unknown type names.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage Unknown auto loader type
     */
    public function testFactoryThrowsExceptionForUnknownType()
    {
        $factory = $this->mockFactory();

        $factory->createValidator('autoload', '----unkown-----type---name----', array());
    }

    /**
     * Test that the factory returns an empty array when the composer.json data has no auto loader sections.
     *
     * @return void
     */
    public function testFactoryReturnsEmptyWhenNoSectionsGiven()
    {
        $factory = $this->mockFactory();

        $this->assertEquals(array(), $factory->createFromComposerJson(array()));
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
            array(
                'autoload' => array(
                    'psr-0' => array('Vendor\\' => 'src')
                ),
                'autoload-dev' => array(
                    'psr-4' => array('Vendor\\' => 'src')
                ),
            )
        );

        $this->assertCount(2, $validators);
    }
}
