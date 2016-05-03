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

namespace PhpCodeQuality\AutoloadValidation\Report;

use PhpCodeQuality\AutoloadValidation\Exception\AppendViolationException;
use PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface;
use PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface;

/**
 * This class is for keeping track of all the violations and generating a proper report then.
 */
class Report
{
    /**
     * List of registered destinations.
     *
     * @var DestinationInterface[]
     */
    private $destinations = array();

    /**
     * The violations.
     *
     * @var array[ViolationInterface]
     */
    private $violations = array();

    /**
     * Determines if severities shall get overridden.
     *
     * @var string[]
     */
    private $severityMap;

    /**
     * Create a new instance.
     *
     * @param DestinationInterface[] $destinations The destinations to add.
     *
     * @param array                  $severityMap  Map to override severities.
     */
    public function __construct($destinations, $severityMap = array())
    {
        $this->severityMap = $severityMap;
        $this->addDestinations($destinations);
    }

    /**
     * Add a violation to the report.
     *
     * @param ViolationInterface $violation The violation to add.
     *
     * @param string             $severity  The severity of the violation.
     *
     * @return void
     *
     * @throws AppendViolationException When one or more destinations did not accept the violation.
     */
    public function append(ViolationInterface $violation, $severity = DestinationInterface::SEVERITY_ERROR)
    {
        $exceptions = array();
        $severity   = $this->mapSeverity($severity);

        // If severity has been silenced, exit.
        if (empty($severity)) {
            return;
        }

        $this->violations[$severity][] = $violation;

        foreach ($this->destinations as $destination) {
            try {
                $destination->append($violation, $severity);
            } catch (\Exception $exception) {
                $exceptions[] = $exception;
            }
        }

        if ($exceptions) {
            throw new AppendViolationException('Could not append violation ' . get_class($violation), $exceptions);
        }
    }

    /**
     * Convenience method to append violations as error.
     *
     * @param ViolationInterface $violation The violation to add.
     *
     * @return void
     */
    public function error(ViolationInterface $violation)
    {
        $this->append($violation, DestinationInterface::SEVERITY_ERROR);
    }

    /**
     * Convenience method to append violations as error.
     *
     * @param ViolationInterface $violation The violation to add.
     *
     * @return void
     */
    public function warn(ViolationInterface $violation)
    {
        $this->append($violation, DestinationInterface::SEVERITY_WARNING);
    }

    /**
     * Check if violations of a certain severity have been added.
     *
     * @param string $severity The severity type.
     *
     * @return bool
     */
    public function has($severity)
    {
        return isset($this->violations[$this->mapSeverity($severity)]);
    }

    /**
     * Check if errors have been added.
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->has(DestinationInterface::SEVERITY_ERROR);
    }

    /**
     * Check if warnings have been added.
     *
     * @return bool
     */
    public function hasWarning()
    {
        return $this->has(DestinationInterface::SEVERITY_WARNING);
    }

    /**
     * Get violations of a certain severity.
     *
     * @param string $severity The severity type.
     *
     * @return ViolationInterface[]
     */
    public function get($severity)
    {
        $severity = $this->mapSeverity($severity);
        if (!isset($this->violations[$severity])) {
            return array();
        }

        return $this->violations[$severity];
    }

    /**
     * Get violations of severity error.
     *
     * @return ViolationInterface[]
     */
    public function getError()
    {
        return $this->get(DestinationInterface::SEVERITY_ERROR);
    }

    /**
     * Get violations of severity warning.
     *
     * @return ViolationInterface[]
     */
    public function getWarning()
    {
        return $this->get(DestinationInterface::SEVERITY_WARNING);
    }

    /**
     * Map the severity from the original value to the configured value.
     *
     * @param string $severity The severity to map.
     *
     * @return string
     *
     * @throws \InvalidArgumentException When the severity is invalid.
     */
    private function mapSeverity($severity)
    {
        if (empty($severity)) {
            throw new \InvalidArgumentException('Invalid severity string');
        }

        if (array_key_exists($severity, $this->severityMap)) {
            return $this->severityMap[$severity];
        }

        return $severity;
    }

    /**
     * Add the passed destinations to the report.
     *
     * @param array $destinations The list of destinations.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When any of the passed objects is not of type DestinationInterface.
     */
    private function addDestinations(array $destinations)
    {
        foreach ($destinations as $destination) {
            if (!$destination instanceof DestinationInterface) {
                throw new \InvalidArgumentException(get_class($destination) . ' is not a valid destination');
            }

            $this->destinations[] = $destination;
        }
    }
}
