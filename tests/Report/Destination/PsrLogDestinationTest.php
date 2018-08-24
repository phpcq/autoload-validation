<?php

/**
 * This file is part of phpcq/autoload-validation.
 *
 * (c) 2018 Christian Schiffler, Tristan Lins
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    phpcq/autoload-validation
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2014-2018 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/phpcq/autoload-validation/blob/master/LICENSE MIT
 * @link       https://github.com/phpcq/autoload-validation
 * @filesource
 */

namespace PhpCodeQuality\AutoloadValidation\Test\Report\Destination;

use PhpCodeQuality\AutoloadValidation\Report\Destination\PsrLogDestination;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * This class tests the Report class.
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
        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\Report\Destination\PsrLogDestination',
            new PsrLogDestination($this->getMockForAbstractClass('Psr\Log\LoggerInterface'))
        );
    }

    /**
     * Test that violations can be added.
     *
     * @return void
     */
    public function testAppend()
    {
        $logger = $this->getMockForAbstractClass('Psr\Log\LoggerInterface');
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::ERROR, 'This is the message', array('p1' => 'par1', 'p2' => 'par2'));

        $destination = new PsrLogDestination($logger);

        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');
        $error->method('getMessage')->willReturn('This is the message');
        $error->method('getParameters')->willReturn(array('p1' => 'par1', 'p2' => 'par2'));

        $destination->append($error);
    }

    /**
     * Test that adding violations raises an exception for unmapped severities.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage Severity is not mapped:
     */
    public function testAppendRaisesExceptionForUnknownLevel()
    {
        $destination = new PsrLogDestination($this->getMockForAbstractClass('Psr\Log\LoggerInterface'));

        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');
        $error->method('getMessage')->willReturn('This is the message');
        $error->method('getParameters')->willReturn(array('par1', 'par2'));

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
            ->getMockBuilder('Symfony\Component\Console\Logger\ConsoleLogger')
            ->disableOriginalConstructor()
            ->getMock();
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::ERROR,
                'This is the message',
                array(
                    'p1' => '<comment>par1</comment>',
                    'p2' => '<comment>par2</comment>',
                    'p3' => '[<comment>sub1</comment>: <comment>\'foo\'</comment>]',
                    'p4' => $object
                )
            );

        $destination = new PsrLogDestination($logger);

        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');
        $error->method('getMessage')->willReturn('This is the message');
        $error->method('getParameters')
            ->willReturn(
                array(
                    'p1' => 'par1',
                    'p2' => 'par2',
                    'p3' => array(
                        'sub1' => 'foo'
                    ),
                    'p4' => $object
                )
            );

        $destination->append($error);
    }
}
