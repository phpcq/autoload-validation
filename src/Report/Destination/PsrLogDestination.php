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

namespace PhpCodeQuality\AutoloadValidation\Report\Destination;

use PhpCodeQuality\AutoloadValidation\Violation\ViolationInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;

/**
 * This class logs to a psr logger instance.
 */
class PsrLogDestination implements DestinationInterface
{
    /**
     * The default level map.
     *
     * @var array
     */
    private $logLevelMap = array(
        DestinationInterface::SEVERITY_ERROR => LogLevel::ERROR,
        DestinationInterface::SEVERITY_WARNING => LogLevel::WARNING,
        DestinationInterface::SEVERITY_INFO => LogLevel::INFO,
    );

    /**
     * The logger to log to.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Determinator if we use a console logger or not.
     *
     * @var bool
     */
    private $isConsole;

    /**
     * Create a new instance.
     *
     * @param LoggerInterface $logger       The logger to log to.
     *
     * @param array           $logLevelMap  The log level map.
     */
    public function __construct(LoggerInterface $logger, $logLevelMap = array())
    {
        $this->logger      = $logger;
        $this->isConsole   = $logger instanceof ConsoleLogger;
        $this->logLevelMap = ($logLevelMap + $this->logLevelMap);
    }

    /**
     * {@inheritDoc}
     */
    public function append(ViolationInterface $violation, $severity = DestinationInterface::SEVERITY_ERROR)
    {
        $message    = $violation->getMessage();
        $parameters = $violation->getParameters();

        if ($this->isConsole) {
            $parameters = $this->prepareContext($parameters);
        }

        $this->logger->log($this->mapLogLevel($severity), $message, $parameters);
    }

    /**
     * Map the severity to a log level.
     *
     * @param string $severity The severity.
     *
     * @return string
     *
     * @throws \InvalidArgumentException For non mapped severities.
     */
    private function mapLogLevel($severity)
    {
        if (!array_key_exists($severity, $this->logLevelMap)) {
            throw new \InvalidArgumentException('Severity is not mapped: ' . $severity);
        }

        return $this->logLevelMap[$severity];
    }

    /**
     * Prepare the log parameters if we are using a console logger.
     *
     * Parameters will get encapsulated with <comment>parameter</comment> values.
     *
     * @param mixed[] $parameters The parameters.
     *
     * @return mixed[]
     */
    private function prepareContext($parameters)
    {
        $new = array();
        // FIXME: we should perform better formatting here, like inverting the color or such.
        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                $subNew = array();
                foreach ($value as $index => $subValue) {
                    $subNew[] = sprintf(
                        '<comment>%s</comment>: <comment>%s</comment>',
                        $index,
                        var_export($subValue, true)
                    );
                }
                $new[$name] = sprintf('[%s]', implode(', ', $subNew));

                continue;
            }
            if (!is_object($value) || method_exists($value, '__toString')) {
                $new[$name] = sprintf('<comment>%s</comment>', $value);

                continue;
            }

            $new[$name] = $value;
        }

        return $new;
    }
}
