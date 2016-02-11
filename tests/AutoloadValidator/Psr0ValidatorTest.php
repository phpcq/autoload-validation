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
            $this->mockLogger()
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
            $this->mockLogger()
        );

        $loader = new ClassLoader();

        $validator->addToLoader($loader);

        $this->assertEquals(
            array(
                'Vendor\\A\\' => array('/some/dir/src'),
                'Vendor\\B\\' => array('/some/dir/another/dir')
            ),
            $loader->getPrefixes()
        );
    }

    /**
     * Test that an error is reported when the namespace is invalid.
     *
     * @return void
     */
    public function testScanAddsErrorWhenNameSpaceIsNumeric()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->once())->method('error')->with(
            Psr0Validator::ERROR_PSR0_NAMESPACE_INVALID,
            array(
                'path'      => '/some/dir/src',
                'prefix'    => 0,
                'name'      => 'autoload.psr-0',
            )
        );

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->never())
            ->method('scan');

        $validator = new Psr0Validator(
            'autoload',
            array('/src'),
            '/some/dir',
            $generator,
            $logger
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
        $logger = $this->mockLogger();
        $logger->expects($this->once())->method('error')->with(
            Psr0Validator::ERROR_PSR0_NO_CLASSES_FOUND_IN_PATH,
            array(
                'path'   => '/some/dir/src/',
                'prefix' => 'Vendor\\',
                'name'   => 'autoload.psr-0'
            )
        );

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->once())
            ->method('scan')
            ->with('/some/dir/src/', null)
            ->willReturn(array());

        $validator = new Psr0Validator(
            'autoload',
            array('Vendor\\' => 'src/'),
            '/some/dir',
            $generator,
            $logger
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
        $logger = $this->mockLogger();
        $logger->expects($this->never())->method('error');

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
            $logger
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
                    Psr0Validator::ERROR_PSR0_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class'       => 'Acme\Log\Writer\File_Writer',
                        'file-is'     => '/acme-log-writer/lib/Acme/Log/Writer/File_Writerr.php',
                        'file-should' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                        'prefix'      => 'Acme\Log\Writer\\',
                        'name'        => 'autoload.psr-0'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writerr.php')
            ),
            array(
                array(
                    Psr0Validator::ERROR_PSR0_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'       => 'Acme\Log\File_Writer',
                        'prefix'      => 'Acme\Log\Writer\\',
                        'detected'    => 'Acme\Log',
                        'directory'   => '/acme-log-writer/lib',
                        'name'        => 'autoload.psr-0'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\File_Writer' => '/acme-log-writer/lib/Acme/Log/File_Writer/File_Writer.php')
            ),
            array(
                array(
                    Psr0Validator::ERROR_PSR0_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'       => 'Symfony\Core\Request',
                        'prefix'      => 'Symfony\Coreeeeeee\\',
                        'detected'    => 'Symfony\Core',
                        'directory'   => '/vendor/symfony/core',
                        'name'        => 'autoload.psr-0'
                    )
                ),
                array('Symfony\Coreeeeeee\\' => 'vendor/symfony/core'),
                array('Symfony\Core\Request' => '/vendor/symfony/core/Symfony/Core/Request.php')
            ),
            array(
                array(
                    Psr0Validator::ERROR_PSR0_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'       => 'Zend\Acl',
                        'prefix'      => 'Zend\Acl\\',
                        'detected'    => 'Zend',
                        'directory'   => '/usr/includes',
                        'name'        => 'autoload.psr-0'
                    )
                ),
                array('Zend\Acl\\' => 'usr/includes'),
                array('Zend\Acl' => '/usr/includes/Zend/Acl.php')
            ),
            array(
                array(
                    Psr0Validator::ERROR_PSR0_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class'       => 'Acme\Log\Writer\File_Writer',
                        'file-is'     => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                        'file-should' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                        'prefix'      => 'Acme\Log\Writer\\',
                        'name'        => 'autoload.psr-0'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php')
            ),
            array(
                array(
                    Psr0Validator::ERROR_PSR0_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class'       => 'Acme\Log\Writer\File_Writer',
                        'file-is'     => '/acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                        'file-should' => '/acme-log-writer/lib/Acme/Log/Writer/File/Writer.php',
                        'prefix'      => 'Acme\\',
                        'name'        => 'autoload.psr-0'
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
                    Psr0Validator::ERROR_PSR0_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class'       => 'Vendor_PearStyle_NameSpace_ClassNam',
                        'file-is'     => '/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'file-should' => '/usr/includes/Vendor/PearStyle/NameSpace/ClassNam.php',
                        'prefix'      => 'Vendor_PearStyle_NameSpace_',
                        'name'        => 'autoload.psr-0'
                    )
                ),
                array('Vendor_PearStyle_NameSpace_' => 'usr/includes'),
                array('Vendor_PearStyle_NameSpace_ClassNam' => '/includes/Vendor/PearStyle/NameSpace/ClassName.php')
            ),
            array(
                array(
                    Psr0Validator::ERROR_PSR0_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class'       => 'Vendor_PearStyle_NameSpace_ClassName',
                        'file-is'     => '/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'file-should' => '/usr/includes/Vendor/PearStyle/NameSpace/ClassName.php',
                        'prefix'      => 'Vendor_PearStyle_NameSpace',
                        'name'        => 'autoload.psr-0'
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
        $logger = $this->mockLogger();
        if (null === $expectedError) {
            $logger->expects($this->never())->method('error');
        } else {
            $logger->expects($this->once())->method('error')->getMatcher()->parametersMatcher =
                new \PHPUnit_Framework_MockObject_Matcher_Parameters($expectedError);
        }

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->once())
            ->method('scan')
            ->willReturn($classMap);

        $validator = new Psr0Validator('autoload', $content, '/', $generator, $logger);

        $validator->validate();
    }
}
