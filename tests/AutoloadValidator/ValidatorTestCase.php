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

use PhpCodeQuality\AutoloadValidation\ClassMapGenerator;
use Psr\Log\LoggerInterface;

/**
 * This class is the base test case for testing validators.
 *
 * @runInSeparateProcess
 */
class ValidatorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Create a mocked logger.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function mockLogger()
    {
        return $this
            ->getMockBuilder('Psr\Log\LoggerInterface')
            ->getMockForAbstractClass();
    }

    /**
     * Mock a class map generator.
     *
     * @param array $classMap The class map to return.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ClassMapGenerator
     */
    protected function mockClassMapGenerator($classMap = array())
    {
        $mock = $this
            ->getMockBuilder('PhpCodeQuality\AutoloadValidation\ClassMapGenerator')
            ->setMethods(array('scan'))
            ->getMock();
        if ($classMap) {
            $mock->method('scan')->willReturn($classMap);
        }

        return $mock;
    }
}
