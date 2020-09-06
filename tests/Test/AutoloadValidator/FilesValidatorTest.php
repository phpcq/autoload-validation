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

use PhpCodeQuality\AutoloadValidation\AutoloadValidator\FilesValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AbstractValidator;
use PhpCodeQuality\AutoloadValidation\Violation\Files\FileNotFoundViolation;

/**
 * This class tests the FilesValidator.
 *
 * @covers \PhpCodeQuality\AutoloadValidation\AutoloadValidator\FilesValidator
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
            ['/src'],
            '/some/dir',
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        self::assertInstanceOf(AbstractValidator::class, $validator);
    }

    /**
     * Test that the validator does not call any method on the loader.
     *
     * @return void
     */
    public function testAddToLoader()
    {
        $fixture = \dirname(\dirname(\realpath(__DIR__))) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'files_loader.php';

        $validator = new FilesValidator(
            'autoload.files',
            [\basename($fixture)],
            \dirname($fixture),
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        $loaders = $validator->getLoader();

        self::assertInstanceOf('Closure', $loaders);
    }

    /**
     * Test that no error is reported when the files have been found.
     *
     * @return void
     */
    public function testScanDoesNotAddsErrorWhenFileFound()
    {
        $validator = new FilesValidator(
            'autoload.files',
            array(\basename(__FILE__)),
            __DIR__,
            $this->mockClassMapGenerator(),
            $this->mockReport()
        );

        $validator->validate();
    }

    /**
     * Test that an error is reported when a file has not been found.
     *
     * @return void
     */
    public function testScanAddsErrorWhenFileNotFound()
    {
        $validator = new FilesValidator(
            'autoload.files',
            ['does/not/exist'],
            __DIR__,
            $this->mockClassMapGenerator(),
            $this->mockReport(
                FileNotFoundViolation::class,
                [
                    'fileEntry'     => 'does/not/exist',
                    'validatorName' => 'autoload.files'
                ]
            )
        );

        $validator->validate();
    }
}
