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

// WARNING!!!! This file is just a reference and should not be used literally.
use PhpCodeQuality\AutoloadValidation\ClassLoader\EnumeratingClassLoader;
use PhpCodeQuality\AutoloadValidation\ClassMapGenerator;
use PhpCodeQuality\AutoloadValidation\Exception\ParentClassNotFoundException;

// Support for Contao 4.1.
if (is_dir($path = getcwd() . '/vendor/contao/core-bundle/src/Resources/contao')) {
    $loader = new \Composer\Autoload\ClassLoader();
    foreach (array(
        'classes',
        'controllers',
        'drivers',
        'elements',
        'forms',
        'library',
        'models',
        'modules',
        'pages',
        'widgets'
    ) as $subDir) {
        $classMap = ClassMapGenerator::createMap($path . '/' . $subDir);
        $loader->addClassMap($classMap);
    }
    $loader->register();
}

// This is the hack to mimic the Contao auto loader.
spl_autoload_register(
    function ($class) {
        if (substr($class, 0, 7) === 'Contao\\') {
            return null;
        }
        try {
            spl_autoload_call('Contao\\' . $class);
        } catch (ParentClassNotFoundException $exception) {
            return null;
        }
        if (EnumeratingClassLoader::isLoaded('Contao\\' . $class) && !EnumeratingClassLoader::isLoaded($class)) {
            class_alias('Contao\\' . $class, $class);
            return true;
        }

        return null;
    }
);
