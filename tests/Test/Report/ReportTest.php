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

namespace PhpCodeQuality\AutoloadValidation\Test\Report;

use PhpCodeQuality\AutoloadValidation\Exception\AppendViolationException;
use PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface;
use PhpCodeQuality\AutoloadValidation\Report\Report;
use PHPUnit\Framework\TestCase;
use PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface;

/**
 * This class tests the Report class.
 *
 * @covers \PhpCodeQuality\AutoloadValidation\Report\Report
 */
class ReportTest extends TestCase
{
    /**
     * Test that the report can be created with valid arguments.
     *
     * @return void
     */
    public function testCreation()
    {
        self::assertInstanceOf(
            Report::class,
            new Report([$this->getMockForAbstractClass(DestinationInterface::class)])
        );
    }

    /**
     * Test that the report can not be created with invalid arguments.
     *
     * @return void
     */
    public function testCreationWithInvalidArguments()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }

        new Report([new \stdClass()]);
    }

    /**
     * Test that appending errors works.
     *
     * @return void
     */
    public function testAppendError()
    {
        $report = new Report(array());
        $error  = $this->getMockForAbstractClass(ViolationInterface::class);
        $report->error($error);

        self::assertTrue($report->hasError());
        self::assertFalse($report->hasWarning());
        self::assertFalse($report->has('unknown'));
        self::assertEquals([$error], $report->getError());
        self::assertEquals([], $report->getWarning());
        self::assertEquals([], $report->get('unknown'));
    }

    /**
     * Test that appending warnings works.
     *
     * @return void
     */
    public function testAppendWarning()
    {
        $report = new Report([]);
        $error  = $this->getMockForAbstractClass(ViolationInterface::class);
        $report->warn($error);

        self::assertFalse($report->hasError());
        self::assertTrue($report->hasWarning());
        self::assertFalse($report->has('unknown'));
        self::assertEquals([], $report->getError());
        self::assertEquals([$error], $report->getWarning());
        self::assertEquals([], $report->get('unknown'));
    }

    /**
     * Test that appending warnings works.
     *
     * @return void
     */
    public function testAppendCustom()
    {
        $report = new Report([]);
        $error  = $this->getMockForAbstractClass(ViolationInterface::class);
        $report->append($error, 'unknown');

        self::assertFalse($report->hasError());
        self::assertFalse($report->hasWarning());
        self::assertTrue($report->has('unknown'));
        self::assertEquals([], $report->getError());
        self::assertEquals([], $report->getWarning());
        self::assertEquals([$error], $report->get('unknown'));
    }

    /**
     * Test that appending delegates to destinations.
     *
     * @return void
     */
    public function testAppendDelegatesToDestinations()
    {
        $error = $this->getMockForAbstractClass(ViolationInterface::class);

        $destination1 = $this->getMockForAbstractClass(DestinationInterface::class);
        $destination2 = $this->getMockForAbstractClass(DestinationInterface::class);

        $destination1->expects(self::once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);
        $destination2->expects(self::once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);

        $report = new Report([$destination1, $destination2]);
        $report->error($error);
    }

    /**
     * Test that exception when appending to destination is queued until end of the loop.
     *
     * @return void
     */
    public function testAppendToDestinationsExceptionAreQueued()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(AppendViolationException::class);
        } else {
            $this->setExpectedException(AppendViolationException::class);
        }

        $error = $this->getMockForAbstractClass(ViolationInterface::class);

        $destination1 = $this->getMockForAbstractClass(DestinationInterface::class);
        $destination2 = $this->getMockForAbstractClass(DestinationInterface::class);

        $destination1
            ->expects(self::once())
            ->method('append')
            ->with($error, DestinationInterface::SEVERITY_ERROR)
            ->willThrowException(new \RuntimeException('DIE!'));
        $destination2->expects(self::once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);

        $report = new Report([$destination1, $destination2]);
        $report->error($error);
    }

    /**
     * Test that overriding severities via severity map works.
     *
     * @return void
     */
    public function testSeverityOverridingWorks()
    {
        $error = $this->getMockForAbstractClass(ViolationInterface::class);

        $destination = $this->getMockForAbstractClass(DestinationInterface::class);
        $destination->expects(self::once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);

        $report = new Report(
            [$destination],
            [DestinationInterface::SEVERITY_WARNING => DestinationInterface::SEVERITY_ERROR]
        );

        $report->warn($error);
    }

    /**
     * Test that silencing severities via severity map works.
     *
     * @return void
     */
    public function testSeveritySilencingWorks()
    {
        $error = $this->getMockForAbstractClass(ViolationInterface::class);

        $destination =
            $this->getMockForAbstractClass(DestinationInterface::class);
        $destination->expects(self::never())->method('append');

        $report = new Report(
            [$destination],
            [DestinationInterface::SEVERITY_WARNING => null]
        );

        $report->warn($error);
    }

    /**
     * Test that an empty severity raises an exception.
     *
     * @return void
     */
    public function testEmptySeverityRaisesException()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }

        $error = $this->getMockForAbstractClass(ViolationInterface::class);

        $destination = $this->getMockForAbstractClass(DestinationInterface::class);
        $destination->expects(self::never())->method('append');

        $report = new Report([$destination]);

        $report->append($error, null);
    }
}
