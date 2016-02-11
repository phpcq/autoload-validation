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
        $validator = new Psr4Validator(
            'autoload',
            array(
                'AVendor\\' => 'src/',
                'Vendor\\Namespace\\' => ''
            ),
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockLogger()
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
        $logger = $this->mockLogger();
        $logger->expects($this->once())->method('error')->with(
            Psr4Validator::ERROR_PSR4_NAMESPACE_INVALID,
            array(
                'path'      => '/some/dir/src',
                'prefix'    => 0,
                'name'      => 'autoload.psr-4',
            )
        );

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->never())
            ->method('scan');

        $validator = new Psr4Validator(
            'autoload',
            array(0 => '/src'),
            '/some/dir',
            $generator,
            $logger
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
        $logger = $this->mockLogger();
        $logger->expects($this->once())->method('error')->with(
            Psr4Validator::ERROR_PSR4_NAMESPACE_MUST_END_WITH_BACKSLASH,
            array(
                'prefix'    => 'Vendor\\Prefix',
                'name'      => 'autoload.psr-4',
            )
        );

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->never())
            ->method('scan');

        $validator = new Psr4Validator(
            'autoload',
            array('Vendor\\Prefix' => '/src'),
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
            Psr4Validator::ERROR_PSR4_NO_CLASSES_FOUND_IN_PATH,
            array(
                'path'   => '/some/dir/src/',
                'prefix' => 'Vendor\\',
                'name'   => 'autoload.psr-4'
            )
        );

        $generator = $this->mockClassMapGenerator();
        $generator
            ->expects($this->once())
            ->method('scan')
            ->with('/some/dir/src/', null)
            ->willReturn(array());

        $validator = new Psr4Validator(
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
                array('Vendor\A\ClassName1' => '/some/dir/src/ClassName1.php'),
                array('Vendor\B\ClassName2' => '/some/dir/another/dir/ClassName2.php')
            );

        $validator = new Psr4Validator(
            'autoload',
            array(
                'Vendor\\A\\' => 'src',
                'Vendor\\B\\' => 'another/dir'
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
                    Psr4Validator::ERROR_PSR4_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class'       => 'Acme\Log\Writer\File_Writer',
                        'file-is'     => '/acme-log-writer/lib/File_Writerr.php',
                        'file-should' => '/acme-log-writer/lib/File_Writer.php',
                        'prefix'      => 'Acme\Log\Writer\\',
                        'name'        => 'autoload.psr-4'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib'),
                array('Acme\Log\Writer\File_Writer' => '/acme-log-writer/lib/File_Writerr.php')
            ),
            array(
                array(
                    Psr4Validator::ERROR_PSR4_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'       => 'Acme\Log\File_Writer',
                        'detected'    => 'Acme\Log',
                        'prefix'      => 'Acme\Log\Writer\\',
                        'directory'   => '/acme-log-writer/lib/',
                        'name'        => 'autoload.psr-4'
                    )
                ),
                array('Acme\Log\Writer\\' => 'acme-log-writer/lib/'),
                array('Acme\Log\File_Writer' => '/acme-log-writer/lib/File_Writer.php')
            ),
            array(
                array(
                    Psr4Validator::ERROR_PSR4_CLASS_FOUND_IN_WRONG_FILE,
                    array(
                        'class'       => 'Aura\Web\Response\Status',
                        'file-is'     => '/path/to/auraweb/src/Response/Status.php',
                        'file-should' => '/path/to/aura-web/src/Response/Status.php',
                        'prefix'      => 'Aura\Web\\',
                        'name'        => 'autoload.psr-4'
                    )
                ),
                array('Aura\Web\\' => 'path/to/aura-web/src'),
                array('\Aura\Web\Response\Status' => '/path/to/auraweb/src/Response/Status.php')
            ),
            array(
                array(
                    Psr4Validator::ERROR_PSR4_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'       => 'Symfony\Core\Request',
                        'detected'    => 'Symfony\Core',
                        'prefix'      => 'Symfony\Coreeeeeee\\',
                        'directory'   => '/vendor/Symfony/Core',
                        'name'        => 'autoload.psr-4'
                    )
                ),
                array('Symfony\Coreeeeeee\\' => 'vendor/Symfony/Core'),
                array('Symfony\Core\Request' => '/vendor/Symfony/Core/Request.php')
            ),
            array(
                array(
                    Psr4Validator::ERROR_PSR4_DETECTED_DOES_NOT_MATCH_EXPECTED_NAMESPACE,
                    array(
                        'class'       => 'Zend\Acl',
                        'detected'    => 'Zend',
                        'prefix'      => 'Zend\Acl\\',
                        'directory'   => '/usr/includes/Zend',
                        'name'        => 'autoload.psr-4'
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

        $validator = new Psr4Validator('autoload', $content, '/', $generator, $logger);

        $validator->validate();
    }
}
