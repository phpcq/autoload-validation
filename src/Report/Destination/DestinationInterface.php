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

/**
 * This interface describes a report destination.
 */
interface DestinationInterface
{
    /**
     * Name for violations with the severity warning.
     */
    const SEVERITY_ERROR = 'ERROR';

    /**
     * Name for violations with the severity warning.
     */
    const SEVERITY_WARNING = 'WARN';

    /**
     * Name for violations with the severity information.
     */
    const SEVERITY_INFO = 'INFO';

    /**
     * Append the given violation.
     *
     * @param ViolationInterface $violation The violation to append.
     *
     * @param string             $severity  The severity of the violation.
     *
     * @return void
     *
     * @throws \RuntimeException When the violation could not be added.
     */
    public function append(ViolationInterface $violation, $severity = DestinationInterface::SEVERITY_ERROR);
}
