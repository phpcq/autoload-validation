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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\FilesValidator;

/**
 * This class tests the FilesValidator.
 *
 * @runInSeparateProcess
 */
class FilesValidatorTest extends ValidatorTestCase
{
    /**
     * Test that the class can be created.
     *
     * @return void
     */
    public function testCreation()
    {
        $validator = new FilesValidator(
            'autoload.files',
            array('/src'),
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $this->mockLogger();
        $this->assertInstanceOf('PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator', $validator);
    }

    /**
     * Test that the validator does not call any method on the loader.
     *
     * @return void
     */
    public function testAddToLoader()
    {
        $validator = new FilesValidator(
            'autoload.files',
            array(basename(__FILE__)),
            __DIR__,
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $this->mockLogger();

        $loader = $this->getMock('Composer\Autoload\ClassLoader');
        $loader->expects($this->never())->method($this->anything());

        $validator->addToLoader($loader);
    }

    /**
     * Test that no error is reported when the files have been found.
     *
     * @return void
     */
    public function testScanDoesNotAddsErrorWhenFileFound()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->never())->method('error')->with(
            FilesValidator::ERROR_FILES_PATH_NOT_FOUND,
            array(
                'path' => __FILE__,
                'name' => 'autoload.files',
            )
        );

        $validator = new FilesValidator(
            'autoload.files',
            array(basename(__FILE__)),
            __DIR__,
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $logger;

        $validator->validate();
    }

    /**
     * Test that an error is reported when a file has not been found.
     *
     * @return void
     */
    public function testScanAddsErrorWhenFileNotFound()
    {
        $logger = $this->mockLogger();
        $logger->expects($this->once())->method('error')->with(
            FilesValidator::ERROR_FILES_PATH_NOT_FOUND,
            array(
                'path' => __DIR__ . '/does/not/exist',
                'name' => 'autoload.files',
            )
        );

        $validator = new FilesValidator(
            'autoload.files',
            array('does/not/exist'),
            __DIR__,
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );
        $validator->logger = $logger;

        $validator->validate();
    }
}
