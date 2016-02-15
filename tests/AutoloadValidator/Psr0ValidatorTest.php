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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\Psr0Validator;

/**
 * This class tests the Psr0Validator.
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
            array(
                'Vendor\\A\\' => '/src',
                'Vendor\\B\\' => '/another/dir'
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
        $validator = new Psr0Validator(
            'autoload',
            array(
                'Vendor\\A\\' => '/src',
                'Vendor\\B\\' => '/another/dir'
            ),
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        $loader = $validator->getLoader();

        $this->assertInstanceOf('Composer\Autoload\ClassLoader', $loader[0]);
        $this->assertEquals(
            array(
                'Vendor\\A\\' => array('/some/dir/src'),
                'Vendor\\B\\' => array('/some/dir/another/dir')
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
            ->expects($this->never())
            ->method('scan');

        $validator = new Psr0Validator(
            'autoload.psr-0',
            array('/src'),
            '/some/dir',
            $generator,
            $this->mockReport(
                'PhpCodeQuality\AutoloadValidation\Violation\Psr0\NameSpaceInvalidViolation',
                array(
                    'path'          => '/some/dir/src',
                    'psr0Prefix'    => 0,
                    'validatorName' => 'autoload.psr-0',
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

        $validator = new Psr0Validator(
            'autoload.psr-0',
            array('Vendor\\' => 'src/'),
            '/some/dir',
            $generator,
            $this->mockReport(
                'PhpCodeQuality\AutoloadValidation\Violation\Psr0\NoClassesFoundInPathViolation',
                array(
                    'path'          => '/some/dir/src/',
                    'psr0Prefix'    => 'Vendor\\',
                    'validatorName' => 'autoload.psr-0'
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
        $report = $this->mockReport();
        $report->expects($this->never())->method('append');

        $generator = $this->mockClassMapGenerator();
        $generator->method('scan')
            ->withConsecutive(
                array('/some/dir/src'),
                array('/some/dir/another/dir')
            )
            ->willReturnOnConsecutiveCalls(
                array('Vendor\A\ClassName1' => '/some/dir/src/Vendor/A/ClassName1.php'),
                array('Vendor\B\ClassName2' => '/some/dir/another/dir/Vendor/B/ClassName2.php')
            );

        $validator = new Psr0Validator(
            'autoload',
            array(
                'Vendor\\A\\' => '/src',
                'Vendor\\B\\' => '/another/dir'
            ),
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
        return array(
            array(
                null,
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php')
            ),
            array(
                null,
                array('Acme\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php')
            ),
            array(
                null,
                array('' => 'acme-log-writer/lib'),
                array('Acme_Log\Writer_File\Writer' => '/acme-log-writer/lib/Acme_Log/Writer_File/Writer.php')
            ),
            array(
                null,
                array('Acme_Log\Writer_File\\' => 'acme-log-writer/lib'),
                array('Acme_Log\Writer_File\Writer' => '/acme-log-writer/lib/Acme_Log/Writer_File/Writer.php')
            ),
            array(
                null,
                array('' => 'acme-log-writer/lib'),
                array('Acme_Log_Writer_File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php')
            ),
            array(
                null,
                array('Zend\\' => 'usr/includes'),
                array('Zend\Acl' => '/usr/includes/Zend/Acl.php')
            ),
            array(
                null,
                array('ContaoCommunityAlliance\\' => 'usr/includes'),
                array('ContaoCommunityAlliance\Dca\Builder\Builder'
                      => '/usr/includes/ContaoCommunityAlliance/Dca/Builder/Builder.php'
                )
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespaceShouldEndWithBackslashViolation',
                    array(
                        'psr0Prefix'    => 'Acme\Log\Writer',
                        'path'          => '/acme-log-writer/lib',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Acme\Log\Writer' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File/Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\ClassFoundInWrongFileViolation',
                    array(
                        'class'         => 'Acme\Log\Writer\File_Writer',
                        'fileIs'        => '/acme-log-writer/lib/Acme/Log/Writer/File_Writerr.php',
                        'fileShould'    => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                        'psr0Prefix'    => 'Acme\Log\Writer\\',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writerr.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespacePrefixMismatchViolation',
                    array(
                        'class'         => 'Acme\Log\File_Writer',
                        'psr0Prefix'    => 'Acme\Log\Writer\\',
                        'namespace'     => 'Acme\Log',
                        'path'          => '/acme-log-writer/lib',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\File_Writer' => '/acme-log-writer/lib/Acme/Log/File_Writer/File_Writer.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespacePrefixMismatchViolation',
                    array(
                        'class'         => 'Symfony\Core\Request',
                        'psr0Prefix'    => 'Symfony\Coreeeeeee\\',
                        'namespace'     => 'Symfony\Core',
                        'path'          => '/vendor/symfony/core',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Symfony\Coreeeeeee\\' => 'vendor/symfony/core'),
                array('Symfony\Core\Request' => '/vendor/symfony/core/Symfony/Core/Request.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\NamespacePrefixMismatchViolation',
                    array(
                        'class'         => 'Zend\Acl',
                        'psr0Prefix'    => 'Zend\Acl\\',
                        'namespace'     => 'Zend',
                        'path'          => '/usr/includes',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Zend\Acl\\' => 'usr/includes'),
                array('Zend\Acl' => '/usr/includes/Zend/Acl.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\ClassFoundInWrongFileViolation',
                    array(
                        'class'         => 'Acme\Log\Writer\File_Writer',
                        'fileIs'        => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                        'fileShould'    => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                        'psr0Prefix'    => 'Acme\Log\Writer\\',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\ClassFoundInWrongFileViolation',
                    array(
                        'class'         => 'Acme\Log\Writer\File_Writer',
                        'fileIs'        => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                        'fileShould'    => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                        'psr0Prefix'    => 'Acme\\',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Acme\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php')
            ),
            array(
                null,
                array('Foo\\' => 'src/'),
                array('Foo\\Bar\\Baz' => '/src/Foo/Bar/Baz.php')
            ),
            array(
                null,
                array('Vendor_PearStyle_NameSpace_' => 'includes'),
                array('Vendor_PearStyle_NameSpace_ClassName' => '/includes/Vendor/PearStyle/NameSpace/ClassName.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\ClassFoundInWrongFileViolation',
                    array(
                        'class'         => 'Vendor_PearStyle_NameSpace_ClassNam',
                        'fileIs'        => '/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'fileShould'    => '/usr/includes/Vendor/PearStyle/NameSpace/ClassNam.php',
                        'psr0Prefix'    => 'Vendor_PearStyle_NameSpace_',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Vendor_PearStyle_NameSpace_' => 'usr/includes'),
                array('Vendor_PearStyle_NameSpace_ClassNam' => '/includes/Vendor/PearStyle/NameSpace/ClassName.php')
            ),
            array(
                array(
                    'PhpCodeQuality\AutoloadValidation\Violation\Psr0\ClassFoundInWrongFileViolation',
                    array(
                        'class'         => 'Vendor_PearStyle_NameSpace_ClassName',
                        'fileIs'        => '/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'fileShould'    => '/usr/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'psr0Prefix'    => 'Vendor_PearStyle_NameSpace',
                        'validatorName' => 'autoload.psr-0'
                    )
                ),
                array('Vendor_PearStyle_NameSpace' => 'usr/includes'),
                array('Vendor_PearStyle_NameSpace_ClassName' => '/includes/Vendor/PearStyle/NameSpace/ClassName.php')
            ),
            array(
                null,
                array('Foo\\' => 'src/'),
                array('\\Foo\\Bar\\Baz' => '/src/Foo/Bar/Baz.php')
            ),
            array(
                null,
                array('Foo\\Bar\\Baz' => 'src'),
                array('Foo\\Bar\\Baz' => '/src/Foo/Bar/Baz.php')
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

        $validator = new Psr0Validator('autoload.psr-0', $content, '/', $generator, $report);

        $validator->validate();
    }
}
