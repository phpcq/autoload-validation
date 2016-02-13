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

namespace PhpCodeQuality\AutoloadValidation\AutoloadValidator;

use Composer\Autoload\ClassLoader;

/**
 * This class validates a "files" entry from composer "autoload" sections.
 */
class FilesValidator extends AbstractValidator
{
    /**
     * The name of the validator.
     */
    const NAME = 'files';

    /**
     * This error is shown when a path could not be found.
     */
    const ERROR_FILES_PATH_NOT_FOUND = '{name}: Path {path} could not be found.';

    /**
     * {@inheritDoc}
     */
    public function addToLoader(ClassLoader $loader)
    {
        $this->validate();

        // No op.
    }

    /**
     * {@inheritDoc}
     */
    protected function doValidate()
    {
        // Scan all directories mentioned and validate the class map against the entries.
        foreach ($this->information as $path) {
            $subPath = str_replace('//', '/', $this->baseDir . '/' . $path);
            if (!realpath($subPath)) {
                $this->error(static::ERROR_FILES_PATH_NOT_FOUND, array('path' => $subPath));
            }
        }
    }
}