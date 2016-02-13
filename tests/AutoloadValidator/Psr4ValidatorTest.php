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
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr4Validator;

/**
 * This class tests the Psr4Validator.
 */
class Psr4ValidatorTest extends ValidatorTestCase
{
    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $validator = new Psr4Validator(
            'autoload',
            array(
                'AVendor\\' => 'src/',
                'Vendor\\Namespace\\' => ''
            ),
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator', $validator);
    }

    /**
     * Test that the validator adds the prefixes to the loader.
     *
     * @return void
     */
    public function testAddToLoader()
    {
        $validator = new Psr4Validator(
            'autoload.psr-4',
            array(
                'AVendor\\' => 'src/',
                'Vendor\\Namespace\\' => ''
            ),
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        $loader = new ClassLoader();

        $validator->addToLoader($loader);

        $this->assertEquals(
            array(
                'AVendor\\' => array('/some/dir/src/'),
                'Vendor\\Namespace\\' => array('/some/dir/')
            ),
            $loader->getPrefixesPsr4()
        );
    }

    /**
     * Test that an error is reported when the namespace is invalid.
     *
     * @return void
     */
    public function testScanAddsErrorWhenNameSpaceIsNumeric()
    {
        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->never())
            ->method('scan');

        $validator = new Psr4Validator(
            'autoload.psr-4',
            array(0 => '/src'),
            '/some/dir',
            $generator,
            $this->mockReport(
                'PhpCodeQuality\AutoloadValidation\Violation\Psr4\NameSpaceInvalidViolation',
                array(
                    'validatorName' => 'autoload.psr-4',
                    'path' => '/src',
                    'psr4Prefix' => 0
                )
            )
        );

        $validator->validate();
    }

    /**
     * Test that an error is reported when the prefix does not end in backslash.
     *
     * @return void
     */
    public function testScanAddsErrorWhenNameSpaceDoesNotEndWithBackslash()
    {
        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->never())
            ->method('scan');

        $validator = new Psr4Validator(
            'autoload.psr-4',
            array('Vendor\\Prefix' => '/src'),
            '/some/dir',
            $generator,
            $this->mockReport(
                'PhpCodeQuality\AutoloadValidation\Violation\Psr4\NamespaceMustEndWithBackslashViolation',
                array(
                    'validatorName' => 'autoload.psr-4',
                    'path' => '/src',
                    'psr4Prefix' => 'Vendor\\Prefix'
                )
            )
        );

        $validator->validate();
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
            ->expects($this->once())
            ->method('scan')
            ->with('/some/dir/src/', null)
            ->willReturn(array());

        $validator = new Psr4Validator(
            'autoload.psr-4',
            array('Vendor\\' => 'src/'),
            '/some/dir',
            $generator,
            $this->mockReport(
                'PhpCodeQuality\AutoloadValidation\Violation\Psr4\NoClassesFoundInPathViolation',
                array(
                    'validatorName' => 'autoload.psr-4',
                    'path' => 'src/',
                    'psr4Prefix' => 'Vendor\\'
                )
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
                array('/some/dir/src'),
                array('/some/dir/another/dir')
            )
            ->willReturnOnConsecutiveCalls(
                array('Vendor\A\ClassName1' => '/some/dir/src/ClassName1.php'),
                array('Vendor\B\ClassName2' => '/some/dir/another/dir/ClassName2.php')
            );

        $validator = new Psr4Validator(
            'autoload.psr-4',
            array(
                'Vendor\\A\\' => 'src',
                'Vendor\\B\\' => 'another/dir'
            ),
            '/some/dir',
            $generator,
            $this->mockReport()
        );

        $validator->validate();

        $classMap = $validator->getClassMap();
        $classes  = iterator_to_array($classMap);
        $this->assertEquals(array('Vendor\A\ClassName1', 'Vendor\B\ClassName2'), array_keys($classes));
        $this->assertEquals('/some/dir/src/ClassName1.php', $classMap->getFileFor('Vendor\A\ClassName1'));
        $this->assertEquals('/some/dir/another/dir/ClassName2.php', $classMap->getFileFor('Vendor\B\ClassName2'));
    }

    /**
     * Data provider for testValidate.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function providerValidate()
    {
        return array(
            array(
                null,
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/File_Writer.php')
            ),
            array(
                null,
                array('Aura\Web\\' => 'path/to/aura-web/src'),
                array('\Aura\Web\Response\Status' => '/path/to/aura-web/src/Response/Status.php')
            ),
            array(
                null,
                array('Symfony\Core\\' => 'vendor/Symfony/Core'),
                array('Symfony\Core\Request' => '/vendor/Symfony/Core/Request.php')
            ),
            array(
                null,
                array('Zend\\' => 'usr/includes/Zend'),
                array('Zend\Acl' => '/usr/includes/Zend/Acl.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr4\ClassFoundInWrongFileViolation',
                    array(
                        'class'         => 'Acme\Log\Writer\File_Writer',
                        'fileIs'        => '/acme-log-writer/lib/File_Writerr.php',
                        'fileShould'    => '/acme-log-writer/lib/File_Writer.php',
                        'psr4Prefix'    => 'Acme\Log\Writer\\',
                        'path'          => '/acme-log-writer/lib',
                        'validatorName' => 'autoload.psr-4'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/File_Writerr.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr4\NamespacePrefixMismatchViolation',
                    array(
                        'class'         => 'Acme\Log\File_Writer',
                        'namespace'     => 'Acme\Log',
                        'psr4Prefix'    => 'Acme\Log\Writer\\',
                        'path'          => '/acme-log-writer/lib/',
                        'validatorName' => 'autoload.psr-4'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib/'),
                array('Acme\Log\File_Writer' => '/acme-log-writer/lib/File_Writer.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr4\ClassFoundInWrongFileViolation',
                    array(
                        'class'         => 'Aura\Web\Response\Status',
                        'fileIs'        => '/path/to/auraweb/src/Response/Status.php',
                        'fileShould'    => '/path/to/aura-web/src/Response/Status.php',
                        'psr4Prefix'    => 'Aura\Web\\',
                        'path'          => '/path/to/aura-web/src',
                        'validatorName' => 'autoload.psr-4'
                    )
                ),
                array('Aura\Web\\' => 'path/to/aura-web/src'),
                array('\Aura\Web\Response\Status' => '/path/to/auraweb/src/Response/Status.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr4\NamespacePrefixMismatchViolation',
                    array(
                        'class'         => 'Symfony\Core\Request',
                        'namespace'     => 'Symfony\Core',
                        'psr4Prefix'    => 'Symfony\Coreeeeeee\\',
                        'path'          => '/vendor/Symfony/Core',
                        'validatorName' => 'autoload.psr-4'
                    )
                ),
                array('Symfony\Coreeeeeee\\' => 'vendor/Symfony/Core'),
                array('Symfony\Core\Request' => '/vendor/Symfony/Core/Request.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr4\NamespacePrefixMismatchViolation',
                    array(
                        'class'         => 'Zend\Acl',
                        'namespace'     => 'Zend',
                        'psr4Prefix'    => 'Zend\Acl\\',
                        'path'          => '/usr/includes/Zend',
                        'validatorName' => 'autoload.psr-4'
                    )
                ),
                array('Zend\Acl\\' => 'usr/includes/Zend'),
                array('Zend\Acl' => '/usr/includes/Zend/Acl.php')
            ),
        );
    }

    /**
     * Test that an error is reported when no classes have been found.
     *
     * @param array|null $expectedError The expected error content or null if none.
     *
     * @param array      $content       The autoload content from composer.json.
     *
     * @param array      $classMap      The class map returned from the generator.
     *
     * @return void
     *
     * @dataProvider providerValidate
     */
    public function testValidate($expectedError, $content, $classMap)
    {
        if (null === $expectedError) {
            $report = $this->mockReport();
        } else {
            $report = $this->mockReport(
                $expectedError[0],
                $expectedError[1]
            );
        }

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->once())
            ->method('scan')
            ->willReturn($classMap);

        $validator = new Psr4Validator('autoload.psr-4', $content, '/', $generator, $report);

        $validator->validate();
    }
}
