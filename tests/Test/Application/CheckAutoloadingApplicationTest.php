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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2014-2020 Christian Schiffler <c.schiffler@cyberspectrum.de>, Tristan Lins <tristan@lins.io>
 * @license    https://github.com/phpcq/autoload-validation/blob/master/LICENSE MIT
 * @link       https://github.com/phpcq/autoload-validation
 * @filesource
 */

namespace PhpCodeQuality\AutoloadValidation\Test\Application;

use PhpCodeQuality\AutoloadValidation\Application\CheckAutoloadingApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @covers \PhpCodeQuality\AutoloadValidation\Application\CheckAutoloadingApplication
 * @covers \PhpCodeQuality\AutoloadValidation\Command\CheckAutoloading
 */
class CheckAutoloadingApplicationTest extends TestCase
{
    public function testApplication()
    {
        $input = new ArrayInput(['--help' => '']);
        $output = new TestOutput();

        $application = new CheckAutoloadingApplication();
        self::assertSame($application->doRun($input, $output), 0);
        self::assertNotEmpty($output->output);
        self::assertTrue($application->has('phpcq:check-autoloading'));
        $application->setAutoExit(false);
        self::assertFalse($application->isAutoExitEnabled());
        $application->setAutoExit(true);
        self::assertTrue($application->isAutoExitEnabled());
    }
}
