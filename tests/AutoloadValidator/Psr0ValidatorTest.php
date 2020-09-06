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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr0Validator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator;
use Composer\Autoload\ClassLoader;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NameSpaceInvalidViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NoClassesFoundInPathViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespaceShouldEndWithBackslashViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\ClassFoundInWrongFileViolation;
use PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespacePrefixMismatchViolation;

/**
 * This class tests the Psr0Validator.
 *
 * @covers \PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr0Validator
 */
class Psr0ValidatorTest extends ValidatorTestCase
{
    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $validator = new Psr0Validator(
            'autoload',
            [
                'Vendor\\A\\' => '/src',
                'Vendor\\B\\' => '/another/dir'
            ],
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        self::assertInstanceOf(AbstractValidator::class, $validator);
    }


    /**
     * Test that the validator adds the prefixes to the loader.
     *
     * @return void
     */
    public function testAddToLoader()
    {
        $validator = new Psr0Validator(
            'autoload',
            [
                'Vendor\\A\\' => '/src',
                'Vendor\\B\\' => '/another/dir'
            ],
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        $loader = $validator->getLoader();

        self::assertInstanceOf(ClassLoader::class, $loader[0]);
        self::assertEquals(
            array(
                'Vendor\\A\\' => ['/some/dir/src'],
                'Vendor\\B\\' => ['/some/dir/another/dir']
            ),
            $loader[0]->getPrefixes()
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
            ->expects(self::never())
            ->method('scan');

        $validator = new Psr0Validator(
            'autoload.psr-0',
            ['/src'],
            '/some/dir',
            $generator,
            $this->mockReport(
                NameSpaceInvalidViolation::class,
                [
                    'validatorName' => 'autoload.psr-0',
                    'psr0Prefix'    => 0,
                    'path'          => '/src',
                ]
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
            ->expects(self::once())
            ->method('scan')
            ->with('/some/dir/src/', null)
            ->willReturn([]);

        $validator = new Psr0Validator(
            'autoload.psr-0',
            ['Vendor\\' => 'src/'],
            '/some/dir',
            $generator,
            $this->mockReport(
                NoClassesFoundInPathViolation::class,
                [
                    'path'          => '/some/dir/src/',
                    'psr0Prefix'    => 'Vendor\\',
                    'validatorName' => 'autoload.psr-0'
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
        $report = $this->mockReport();
        $report
            ->expects(self::never())
            ->method('append');

        $generator = $this->mockClassMapGenerator();
        $generator->method('scan')
            ->withConsecutive(
                ['/some/dir/src'],
                ['/some/dir/another/dir']
            )
            ->willReturnOnConsecutiveCalls(
                ['Vendor\A\ClassName1' => '/some/dir/src/Vendor/A/ClassName1.php'],
                ['Vendor\B\ClassName2' => '/some/dir/another/dir/Vendor/B/ClassName2.php']
            );

        $validator = new Psr0Validator(
            'autoload',
            [
                'Vendor\\A\\' => '/src',
                'Vendor\\B\\' => '/another/dir'
            ],
            '/some/dir',
            $generator,
            $report
        );

        $validator->validate();
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
        return [
            [
                null,
                ['Acme\Log\Writer\\' => 'acme-log-writer/lib'],
                ['Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php']
            ],
            [
                null,
                ['Acme\\' => 'acme-log-writer/lib'],
                ['Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php']
            ],
            [
                null,
                ['' => 'acme-log-writer/lib'],
                ['Acme_Log\Writer_File\Writer' => '/acme-log-writer/lib/Acme_Log/Writer_File/Writer.php']
            ],
            [
                null,
                ['Acme_Log\Writer_File\\' => 'acme-log-writer/lib'],
                ['Acme_Log\Writer_File\Writer' => '/acme-log-writer/lib/Acme_Log/Writer_File/Writer.php']
            ],
            [
                null,
                ['' => 'acme-log-writer/lib'],
                ['Acme_Log_Writer_File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php']
            ],
            [
                null,
                ['Zend\\' => 'usr/includes'],
                ['Zend\Acl' => '/usr/includes/Zend/Acl.php']
            ],
            [
                null,
                ['ContaoCommunityAlliance\\' => 'usr/includes'],
                [
                    'ContaoCommunityAlliance\Dca\Builder\Builder'
                      => '/usr/includes/ContaoCommunityAlliance/Dca/Builder/Builder.php'
                ]
            ],
            [
                [
                    NamespaceShouldEndWithBackslashViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Acme\Log\Writer',
                        'path'          => 'acme-log-writer/lib',
                    ]
                ],
                ['Acme\Log\Writer' => 'acme-log-writer/lib'],
                ['Acme\Log\Writer\File/Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php']
            ],
            [
                [
                    ClassFoundInWrongFileViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Acme\Log\Writer\\',
                        'path'          => 'acme-log-writer/lib',
                        'class'         => 'Acme\Log\Writer\File_Writer',
                        'fileIs'        => '/acme-log-writer/lib/Acme/Log/Writer/File_Writerr.php',
                        'fileShould'    => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                    ]
                ],
                ['Acme\Log\Writer\\' => 'acme-log-writer/lib'],
                ['Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writerr.php']
            ],
            [
                [
                    NamespacePrefixMismatchViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Acme\Log\Writer\\',
                        'path'          => 'acme-log-writer/lib',
                        'class'         => 'Acme\Log\File_Writer',
                        'namespace'     => 'Acme\Log',
                    ]
                ],
                ['Acme\Log\Writer\\' => 'acme-log-writer/lib'],
                ['Acme\Log\File_Writer' => '/acme-log-writer/lib/Acme/Log/File_Writer/File_Writer.php']
            ],
            [
                [
                    NamespacePrefixMismatchViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Symfony\Coreeeeeee\\',
                        'path'          => 'vendor/symfony/core',
                        'class'         => 'Symfony\Core\Request',
                        'namespace'     => 'Symfony\Core',
                    ]
                ],
                ['Symfony\Coreeeeeee\\' => 'vendor/symfony/core'],
                ['Symfony\Core\Request' => '/vendor/symfony/core/Symfony/Core/Request.php']
            ],
            [
                [
                    NamespacePrefixMismatchViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Zend\Acl\\',
                        'path'          => 'usr/includes',
                        'class'         => 'Zend\Acl',
                        'namespace'     => 'Zend',
                    ]
                ],
                ['Zend\Acl\\' => 'usr/includes'],
                ['Zend\Acl' => '/usr/includes/Zend/Acl.php']
            ],
            [
                [
                    ClassFoundInWrongFileViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Acme\Log\Writer\\',
                        'path'          => 'acme-log-writer/lib',
                        'class'         => 'Acme\Log\Writer\File_Writer',
                        'fileIs'        => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                        'fileShould'    => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                    ]
                ],
                ['Acme\Log\Writer\\' => 'acme-log-writer/lib'],
                ['Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php']
            ],
            [
                [
                    ClassFoundInWrongFileViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Acme\\',
                        'path'          => 'acme-log-writer/lib',
                        'class'         => 'Acme\Log\Writer\File_Writer',
                        'fileIs'        => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                        'fileShould'    => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                    ]
                ],
                ['Acme\\' => 'acme-log-writer/lib'],
                ['Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php']
            ],
            [
                null,
                ['Foo\\' => 'src/'],
                ['Foo\\Bar\\Baz' => '/src/Foo/Bar/Baz.php']
            ],
            [
                null,
                ['Vendor_PearStyle_NameSpace_' => 'includes'],
                ['Vendor_PearStyle_NameSpace_ClassName' => '/includes/Vendor/PearStyle/NameSpace/ClassName.php']
            ],
            [
                [
                    ClassFoundInWrongFileViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Vendor_PearStyle_NameSpace_',
                        'path'          => 'usr/includes',
                        'class'         => 'Vendor_PearStyle_NameSpace_ClassNam',
                        'fileIs'        => '/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'fileShould'    => '/usr/includes/Vendor/PearStyle/NameSpace/ClassNam.php',
                    ]
                ],
                ['Vendor_PearStyle_NameSpace_' => 'usr/includes'],
                ['Vendor_PearStyle_NameSpace_ClassNam' => '/includes/Vendor/PearStyle/NameSpace/ClassName.php']
            ],
            [
                [
                    ClassFoundInWrongFileViolation::class,
                    [
                        'validatorName' => 'autoload.psr-0',
                        'psr0Prefix'    => 'Vendor_PearStyle_NameSpace',
                        'path'          => 'usr/includes',
                        'class'         => 'Vendor_PearStyle_NameSpace_ClassName',
                        'fileIs'        => '/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'fileShould'    => '/usr/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                    ]
                ],
                ['Vendor_PearStyle_NameSpace' => 'usr/includes'],
                ['Vendor_PearStyle_NameSpace_ClassName' => '/includes/Vendor/PearStyle/NameSpace/ClassName.php']
            ],
            [
                null,
                ['Foo\\' => 'src/'],
                ['\\Foo\\Bar\\Baz' => '/src/Foo/Bar/Baz.php']
            ],
            [
                null,
                ['Foo\\Bar\\Baz' => 'src'],
                ['Foo\\Bar\\Baz' => '/src/Foo/Bar/Baz.php']
            ],
        ];
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
            ->expects(self::once())
            ->method('scan')
            ->willReturn($classMap);

        $validator = new Psr0Validator('autoload.psr-0', $content, '/', $generator, $report);

        $validator->validate();
    }
}
