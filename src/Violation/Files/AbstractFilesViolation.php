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

namespace PhpCodeQuality\AutoloadValidation\Violation\Files;

use PhpCodeQuality\AutoloadValidation\Violation\ValidatorViolation;

/**
 * This violation is originating from a files validator.
 */
abstract class AbstractFilesViolation extends ValidatorViolation
{
    /**
     * The specified file.
     *
     * @var string
     */
    protected $fileEntry;

    /**
     * Create a new instance.
     *
     * @param string $validatorName The name of the originating validator.
     *
     * @param string $fileEntry     The specified file.
     */
    public function __construct($validatorName, $fileEntry)
    {
        parent::__construct($validatorName);
        $this->fileEntry = $fileEntry;
    }
}
