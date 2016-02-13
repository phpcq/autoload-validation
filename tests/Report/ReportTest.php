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

use PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface;
use PhpCodeQuality\AutoloadValidation\Report\Report;

/**
 * This class tests the Report class.
 */
class ReportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the report can be created with valid arguments.
     *
     * @return void
     */
    public function testCreation()
    {
        $this->assertInstanceOf(
            'PhpCodeQuality\AutoloadValidation\Report\Report',
            new Report(
                array(
                    $this->getMockForAbstractClass(
                        'PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface'
                    )
                )
            )
        );
    }

    /**
     * Test that the report can not be created with invalid arguments.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage is not a valid destination
     */
    public function testCreationWithInvalidArguments()
    {
        new Report(array(new \stdClass()));
    }

    /**
     * Test that appending errors works.
     *
     * @return void
     */
    public function testAppendError()
    {
        $report = new Report(array());
        $error  = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');
        $report->error($error);

        $this->assertTrue($report->hasError());
        $this->assertFalse($report->hasWarning());
        $this->assertFalse($report->has('unknown'));
        $this->assertEquals(array($error), $report->getError());
        $this->assertEquals(array(), $report->getWarning());
        $this->assertEquals(array(), $report->get('unknown'));
    }

    /**
     * Test that appending warnings works.
     *
     * @return void
     */
    public function testAppendWarning()
    {
        $report = new Report(array());
        $error  = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');
        $report->warn($error);

        $this->assertFalse($report->hasError());
        $this->assertTrue($report->hasWarning());
        $this->assertFalse($report->has('unknown'));
        $this->assertEquals(array(), $report->getError());
        $this->assertEquals(array($error), $report->getWarning());
        $this->assertEquals(array(), $report->get('unknown'));
    }

    /**
     * Test that appending warnings works.
     *
     * @return void
     */
    public function testAppendCustom()
    {
        $report = new Report(array());
        $error  = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');
        $report->append($error, 'unknown');

        $this->assertFalse($report->hasError());
        $this->assertFalse($report->hasWarning());
        $this->assertTrue($report->has('unknown'));
        $this->assertEquals(array(), $report->getError());
        $this->assertEquals(array(), $report->getWarning());
        $this->assertEquals(array($error), $report->get('unknown'));
    }

    /**
     * Test that appending delegates to destinations.
     *
     * @return void
     */
    public function testAppendDelegatesToDestinations()
    {
        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');

        $destination1 =
            $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface');
        $destination2 =
            $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface');

        $destination1->expects($this->once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);
        $destination2->expects($this->once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);

        $report = new Report(array($destination1, $destination2));
        $report->error($error);
    }

    /**
     * Test that exception when appending to destination is queued until end of the loop.
     *
     * @return void
     *
     * @expectedException \PhpCodeQuality\AutoloadValidation\Exception\AppendViolationException
     *
     * @expectedExceptionMessage Could not append violation
     */
    public function testAppendToDestinationsExceptionAreQueued()
    {
        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');

        $destination1 =
            $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface');
        $destination2 =
            $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface');

        $destination1
            ->expects($this->once())
            ->method('append')
            ->with($error, DestinationInterface::SEVERITY_ERROR)
            ->willThrowException(new \RuntimeException('DIE!'));
        $destination2->expects($this->once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);

        $report = new Report(array($destination1, $destination2));
        $report->error($error);
    }

    /**
     * Test that overriding severities via severity map works.
     *
     * @return void
     */
    public function testSeverityOverridingWorks()
    {
        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');

        $destination =
            $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface');
        $destination->expects($this->once())->method('append')->with($error, DestinationInterface::SEVERITY_ERROR);

        $report = new Report(
            array($destination),
            array(DestinationInterface::SEVERITY_WARNING => DestinationInterface::SEVERITY_ERROR)
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
        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');

        $destination =
            $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface');
        $destination->expects($this->never())->method('append');

        $report = new Report(
            array($destination),
            array(DestinationInterface::SEVERITY_WARNING => null)
        );

        $report->warn($error);
    }

    /**
     * Test that an empty severity raises an exception.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage Invalid severity string
     */
    public function testEmptySeverityRaisesException()
    {
        $error = $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface');

        $destination =
            $this->getMockForAbstractClass('PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface');
        $destination->expects($this->never())->method('append');

        $report = new Report(array($destination));

        $report->append($error, null);
    }
}
