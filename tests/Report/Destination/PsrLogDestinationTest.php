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

namespace PhpCodeQuality\AutoloadValidation\Test\Report\Destination;

use PhpCodeQuality\AutoloadValidation\Report\Destination\PsrLogDestination;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

/**
 * This class tests the Report class.
 *
 * @covers \PhpCodeQuality\AutoloadValidation\Report\Destination\PsrLogDestination
 */
class PsrLogDestinationTest extends TestCase
{
    /**
     * Test that the class can be instantiated.
     *
     * @return void
     */
    public function testCreation()
    {
        self::assertInstanceOf(
            PsrLogDestination::class,
            new PsrLogDestination($this->getMockForAbstractClass(LoggerInterface::class))
        );
    }

    /**
     * Test that violations can be added.
     *
     * @return void
     */
    public function testAppend()
    {
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('log')
            ->with(LogLevel::ERROR, 'This is the message', ['p1' => 'par1', 'p2' => 'par2']);

        $destination = new PsrLogDestination($logger);

        $error = $this->getMockForAbstractClass(ViolationInterface::class);
        $error->method('getMessage')->willReturn('This is the message');
        $error->method('getParameters')->willReturn(['p1' => 'par1', 'p2' => 'par2']);

        $destination->append($error);
    }

    /**
     * Test that adding violations raises an exception for unmapped severities.
     *
     * @return void
     */
    public function testAppendRaisesExceptionForUnknownLevel()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }
        $destination = new PsrLogDestination($this->getMockForAbstractClass(LoggerInterface::class));

        $error = $this->getMockForAbstractClass(ViolationInterface::class);
        $error->method('getMessage')->willReturn('This is the message');
        $error->method('getParameters')->willReturn(['par1', 'par2']);

        $destination->append($error, 'unmapped severity');
    }

    /**
     * Test that violations will get prepared for ConsoleLogger.
     *
     * @return void
     */
    public function testAppendPreparesContextForConsoleLogger()
    {
        $object = new \DateTime();

        $logger = $this
            ->getMockBuilder(ConsoleLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger
            ->expects(self::once())
            ->method('log')
            ->with(
                LogLevel::ERROR,
                'This is the message',
                [
                    'p1' => '<comment>par1</comment>',
                    'p2' => '<comment>par2</comment>',
                    'p3' => '[<comment>sub1</comment>: <comment>\'foo\'</comment>]',
                    'p4' => $object
                ]
            );

        $destination = new PsrLogDestination($logger);

        $error = $this->getMockForAbstractClass(ViolationInterface::class);
        $error->method('getMessage')->willReturn('This is the message');
        $error->method('getParameters')
            ->willReturn(
                [
                    'p1' => 'par1',
                    'p2' => 'par2',
                    'p3' => [
                        'sub1' => 'foo'
                    ],
                    'p4' => $object
                ]
            );

        $destination->append($error);
    }
}
