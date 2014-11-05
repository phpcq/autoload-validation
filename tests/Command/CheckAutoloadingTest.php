<?php

/**
 * This file is part of the Contao Community Alliance Build System tools.
 *
 * @copyright 2014 Contao Community Alliance <https://c-c-a.org>
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package   contao-community-alliance/build-system-tool-autoloading-validation
 * @license   MIT
 * @link      https://c-c-a.org
 */
namespace ContaoCommunityAlliance\BuildSystem\Tool\AutoloadingValidation\Test\Command;

use ContaoCommunityAlliance\BuildSystem\Tool\AutoloadingValidation\Command\CheckAutoloading;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Unit tests for testing CheckAutoloading command class.
 */
class CheckAutoloadingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create a command instance with dummy input and output.
     *
     * @return CheckAutoloading
     */
    protected function getCommand()
    {
        $command = new CheckAutoloading();

        $reflection = new \ReflectionProperty(
            $command,
            'input'
        );
        $reflection->setAccessible(true);
        $reflection->setValue($command, new StringInput(''));

        $reflection = new \ReflectionProperty(
            $command,
            'output'
        );
        $reflection->setAccessible(true);
        $reflection->setValue($command, new BufferedOutput());

        return $command;
    }

    /**
     * Read the output buffer from the output attached to the command.
     *
     * @param CheckAutoloading $command The command.
     *
     * @return string
     */
    protected function getOutputFromCommand($command)
    {
        $reflection = new \ReflectionProperty(
            $command,
            'output'
        );
        $reflection->setAccessible(true);

        $output = $reflection->getValue($command);

        return $output->fetch();
    }

    /**
     * Provide test fixtures for testValidateComposerAutoLoadingPsr4ClassMap().
     *
     * @return array
     */
    public function validateComposerAutoLoadingPsr4ClassMapProvider()
    {
        return array(
            array(
                'classMap'  => array(
                    'Acme\Log\Writer\File_Writer' => './acme-log-writer/lib/File_Writer.php',
                ),
                'subPath'   => './acme-log-writer/lib',
                'namespace' => 'Acme\Log\Writer',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    '\Aura\Web\Response\Status' => '/path/to/aura-web/src/Response/Status.php',
                ),
                'subPath'   => '/path/to/aura-web/src',
                'namespace' => 'Aura\Web',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    'Symfony\Core\Request' => './vendor/Symfony/Core/Request.php',
                ),
                'subPath'   => './vendor/Symfony/Core',
                'namespace' => 'Symfony\Core',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    'Zend\Acl' => '/usr/includes/Zend/Acl.php',
                ),
                'subPath'   => '/usr/includes/Zend',
                'namespace' => 'Zend',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    'Zend\Acl' => '/usr/includes/Zend/Acl.php',
                ),
                'subPath'   => '/usr/includes/Zend',
                'namespace' => 'Zend\\',
                'result'    => true,
            ),

            array(
                'classMap'  => array(
                    'Acme\Log\Writer\File_Writer' => './acme-log-writer/lib/File_Writerr.php',
                ),
                'subPath'   => './acme-log-writer/lib',
                'namespace' => 'Acme\Log\Writer',
                'result'    => false,
            ),
            array(
                'classMap'  => array(
                    'Acme\Log\File_Writer' => './acme-log-writer/lib/File_Writer.php',
                ),
                'subPath'   => './acme-log-writer/lib/',
                'namespace' => 'Acme\Log\Writer',
                'result'    => false,
            ),
            array(
                'classMap'  => array(
                    '\Aura\Web\Response\Status' => '/path/to/auraweb/src/Response/Status.php',
                ),
                'subPath'   => '/path/to/aura-web/src',
                'namespace' => 'Aura\Web',
                'result'    => false,
            ),
            array(
                'classMap'  => array(
                    'Symfony\Core\Request' => './vendor/Symfony/Core/Request.php',
                ),
                'subPath'   => './vendor/Symfony/Core',
                'namespace' => 'Symfony\Coreeeeeee',
                'result'    => false,
            ),
            array(
                'classMap'  => array(
                    'Zend\Acl' => '/usr/includes/Zend/Acl.php',
                ),
                'subPath'   => '/usr/includes/Zend',
                'namespace' => 'Zend\Acl',
                'result'    => false,
            ),
        );
    }

    /**
     * Validate psr-4 auto loading.
     *
     * @dataProvider validateComposerAutoLoadingPsr4ClassMapProvider
     *
     * @param array  $classMap  The class map to analyze.
     *
     * @param string $subPath   The sub path to the psr-4 root.
     *
     * @param string $namespace The namespace prefix within the psr-4 root.
     *
     * @param bool   $result    Expected test result.
     *
     * @return void
     */
    public function testValidateComposerAutoLoadingPsr4ClassMap($classMap, $subPath, $namespace, $result)
    {
        $command = $this->getCommand();
        $this->assertEquals(
            $result,
            $command->validateComposerAutoLoadingPsr4ClassMap(
                $classMap,
                $subPath,
                $namespace
            ),
            $output = $this->getOutputFromCommand($command)
        );

        if ($result) {
            $this->assertEmpty($output, $output);
        } else {
            $this->assertNotEmpty($output, $output);
        }
    }

    /**
     * Provide test fixtures for testValidateComposerAutoLoadingPsr0ClassMap().
     *
     * @return array
     */
    public function validateComposerAutoLoadingPsr0ClassMapProvider()
    {
        return array(
            array(
                'classMap'  => array(
                    'Acme\Log\Writer\File_Writer' => './acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                ),
                'subPath'   => './acme-log-writer/lib',
                'namespace' => 'Acme\Log\Writer',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    'Acme\Log\Writer\File_Writer' => './acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                ),
                'subPath'   => './acme-log-writer/lib',
                'namespace' => 'Acme',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    'Zend\Acl' => '/usr/includes/Zend/Acl.php',
                ),
                'subPath'   => '/usr/includes',
                'namespace' => 'Zend',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    'ContaoCommunityAlliance\Dca\Builder\Builder' => '/usr/includes/ContaoCommunityAlliance/Dca/Builder/Builder.php',
                ),
                'subPath'   => '/usr/includes',
                'namespace' => 'ContaoCommunityAlliance',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                    'Acme\Log\Writer\File_Writer' => './acme-log-writer/lib/Acme/Log/Writer/File_Writerr.php',
                ),
                'subPath'   => './acme-log-writer/lib',
                'namespace' => 'Acme\Log\Writer',
                'result'    => false,
            ),
            array(
                'classMap'  => array(
                    'Acme\Log\File_Writer' => './acme-log-writer/lib/Acme/Log/File_Writer/File_Writer.php',
                ),
                'subPath'   => './acme-log-writer/lib',
                'namespace' => 'Acme\Log\Writer',
                'result'    => false,
            ),
            array(
                'classMap'  => array(
                    'Symfony\Core\Request' => './vendor/symfony/core/Symfony/Core/Request.php',
                ),
                'subPath'   => './vendor/symfony/core',
                'namespace' => 'Symfony\Coreeeeeee',
                'result'    => false,
            ),
            array(
                'classMap'  => array(
                    'Zend\Acl' => '/usr/includes/Zend/Acl.php',
                ),
                'subPath'   => '/usr/includes',
                'namespace' => 'Zend\Acl',
                'result'    => false,
            ),
        );
    }

    /**
     * Validate psr-4 auto loading.
     *
     * @dataProvider validateComposerAutoLoadingPsr0ClassMapProvider
     *
     * @param array  $classMap  The class map to analyze.
     *
     * @param string $subPath   The sub path to the psr-4 root.
     *
     * @param string $namespace The namespace prefix within the psr-4 root.
     *
     * @param bool   $result    Expected test result.
     *
     * @return void
     */
    public function testValidateComposerAutoLoadingPsr0ClassMap($classMap, $subPath, $namespace, $result)
    {
        $command = $this->getCommand();
        $this->assertEquals(
            $result,
            $command->validateComposerAutoLoadingPsr0ClassMap(
                $classMap,
                $subPath,
                $namespace
            ),
            $output = $this->getOutputFromCommand($command)
        );

        if ($result) {
            $this->assertEmpty($output, $output);
        } else {
            $this->assertNotEmpty($output, $output);
        }
    }

    /**
     * Provide test fixtures for testValidateComposerAutoLoadingClassMapClassMap().
     *
     * @return array
     */
    public function validateComposerAutoLoadingClassMapClassMapProvider()
    {
        return array(
            array(
                'classMap'  => array(
                    'Acme\Log\Writer\File_Writer' => './acme-log-writer/lib/Acme/Log/Writer/File_Writer.php',
                ),
                'subPath'   => './acme-log-writer/lib',
                'result'    => true,
            ),
            array(
                'classMap'  => array(
                ),
                'subPath'   => '/usr/includes/Zend',
                'result'    => false,
            ),
        );
    }

    /**
     * Validate psr-4 auto loading.
     *
     * @dataProvider validateComposerAutoLoadingClassMapClassMapProvider
     *
     * @param array  $classMap  The class map to analyze.
     *
     * @param string $subPath   The sub path to the psr-4 root.
     *
     * @param bool   $result    Expected test result.
     *
     * @return void
     */
    public function testValidateComposerAutoLoadingClassMapClassMap($classMap, $subPath, $result)
    {
        $command = $this->getCommand();
        $realMessages = array();
        $this->assertEquals(
            $result,
            $command->validateComposerAutoLoadingClassMapClassMap(
                $classMap,
                $subPath,
                $realMessages
            ),
            $output = $this->getOutputFromCommand($command)
        );

        if ($result) {
            $this->assertEmpty($output, $output);
        } else {
            $this->assertNotEmpty($output, $output);
        }
    }
}
