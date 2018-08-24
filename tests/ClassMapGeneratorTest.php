<?php

/**
 * This file is part of phpcq/autoload-validation.
 *
 * (c) 2018 Christian Schiffler, Tristan Lins
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    phpcq/autoload-validation
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2014-2018 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/phpcq/autoload-validation/blob/master/LICENSE MIT
 * @link       https://github.com/phpcq/autoload-validation
 * @filesource
 */

namespace PhpCodeQuality\AutoloadValidation\Test;

use PhpCodeQuality\AutoloadValidation\ClassMapGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * This class tests the ClassMapGenerator
 */
class ClassMapGeneratorTest extends TestCase
{
    /**
     * Test createMap().
     *
     * @param string   $directory The directory to scan in.
     *
     * @param string[] $expected  The expected class map.
     *
     * @return void
     *
     * @dataProvider getTestCreateMapTests
     */
    public function testCreateMap($directory, $expected)
    {
        $this->assertEqualsNormalized($expected, ClassMapGenerator::createMap($directory));
    }

    /**
     * Data provider for testCreateMap()
     *
     * @return array
     */
    public function getTestCreateMapTests()
    {
        if (PHP_VERSION_ID == 50303) {
            $this->markTestSkipped('Test segfaults on travis 5.3.3 due to ClassMap\LongString');
        }

        $fixtures = realpath(__DIR__).'/fixtures/classmap';

        $data = array(
            array($fixtures . '/Namespaced', array(
                'Namespaced\\Bar' => $fixtures . '/Namespaced/Bar.inc',
                'Namespaced\\Foo' => $fixtures . '/Namespaced/Foo.php',
                'Namespaced\\Baz' => $fixtures . '/Namespaced/Baz.php',
            )),
            array($fixtures . '/beta/NamespaceCollision', array(
                'NamespaceCollision\\A\\B\\Bar' => $fixtures . '/beta/NamespaceCollision/A/B/Bar.php',
                'NamespaceCollision\\A\\B\\Foo' => $fixtures . '/beta/NamespaceCollision/A/B/Foo.php',
            )),
            array($fixtures . '/Pearlike', array(
                'Pearlike_Foo' => $fixtures . '/Pearlike/Foo.php',
                'Pearlike_Bar' => $fixtures . '/Pearlike/Bar.php',
                'Pearlike_Baz' => $fixtures . '/Pearlike/Baz.php',
            )),
            array($fixtures . '/classmap', array(
                'Foo\\Bar\\A'             => $fixtures . '/classmap/sameNsMultipleClasses.php',
                'Foo\\Bar\\B'             => $fixtures . '/classmap/sameNsMultipleClasses.php',
                'Alpha\\A'                => $fixtures . '/classmap/multipleNs.php',
                'Alpha\\B'                => $fixtures . '/classmap/multipleNs.php',
                'A'                       => $fixtures . '/classmap/multipleNs.php',
                'Be\\ta\\A'               => $fixtures . '/classmap/multipleNs.php',
                'Be\\ta\\B'               => $fixtures . '/classmap/multipleNs.php',
                'ClassMap\\SomeInterface' => $fixtures . '/classmap/SomeInterface.php',
                'ClassMap\\SomeParent'    => $fixtures . '/classmap/SomeParent.php',
                'ClassMap\\SomeClass'     => $fixtures . '/classmap/SomeClass.php',
                'ClassMap\\LongString'    => $fixtures . '/classmap/LongString.php',
                'Foo\\LargeClass'         => $fixtures . '/classmap/LargeClass.php',
                'Foo\\LargeGap'           => $fixtures . '/classmap/LargeGap.php',
                'Foo\\MissingSpace'       => $fixtures . '/classmap/MissingSpace.php',
                'Foo\\StripNoise'         => $fixtures . '/classmap/StripNoise.php',
                'Foo\\SlashedA'           => $fixtures . '/classmap/BackslashLineEndingString.php',
                'Foo\\SlashedB'           => $fixtures . '/classmap/BackslashLineEndingString.php',
                'Unicode\\↑\\↑'           => $fixtures . '/classmap/Unicode.php',
            )),
            array(__DIR__.'/fixtures/classmap/template', array()),
        );

        if (PHP_VERSION_ID >= 50400) {
            $data[] = array($fixtures . '/php5.4', array(
                'TFoo'         => $fixtures . '/php5.4/traits.php',
                'CFoo'         => $fixtures . '/php5.4/traits.php',
                'Foo\\TBar'    => $fixtures . '/php5.4/traits.php',
                'Foo\\IBar'    => $fixtures . '/php5.4/traits.php',
                'Foo\\TFooBar' => $fixtures . '/php5.4/traits.php',
                'Foo\\CBar'    => $fixtures . '/php5.4/traits.php',
            ));
        }
        if (PHP_VERSION_ID >= 70000) {
            $data[] = array($fixtures . '/php7.0', array(
                'Dummy\Test\AnonClassHolder' => $fixtures . '/php7.0/anonclass.php',
            ));
        }
        if (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.3', '>=')) {
            $data[] = array($fixtures . '/hhvm3.3', array(
                'FooEnum'       => $fixtures . '/hhvm3.3/HackEnum.php',
                'Foo\BarEnum'   => $fixtures . '/hhvm3.3/NamespacedHackEnum.php',
                'GenericsClass' => $fixtures . '/hhvm3.3/Generics.php',
            ));
        }

        return $data;
    }

    /**
     * Test that createMap() accepts a finder instance.
     *
     * @return void
     */
    public function testCreateMapFinderSupport()
    {
        $this->checkIfFinderIsAvailable();

        $fixtures = realpath(__DIR__).'/fixtures/classmap';
        $finder   = new Finder();
        $finder->files()->in($fixtures . '/beta/NamespaceCollision');

        $this->assertEqualsNormalized(array(
            'NamespaceCollision\\A\\B\\Bar' => $fixtures . '/beta/NamespaceCollision/A/B/Bar.php',
            'NamespaceCollision\\A\\B\\Foo' => $fixtures . '/beta/NamespaceCollision/A/B/Foo.php',
        ), ClassMapGenerator::createMap($finder));
    }

    /**
     * Test that find classes throws an exception when none are found.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     *
     * @expectedExceptionMessage does not exist
     */
    public function testFindClassesThrowsWhenFileDoesNotExist()
    {
        $refl = new \ReflectionClass('PhpCodeQuality\\AutoloadValidation\\ClassMapGenerator');
        $find = $refl->getMethod('findClasses');
        $find->setAccessible(true);

        $find->invoke(null, __DIR__.'/no-file');
    }

    /**
     * Test that ambiguous references are detected.
     *
     * @return void
     */
    public function testAmbiguousReference()
    {
        $this->checkIfFinderIsAvailable();

        $tempDir = $this->getUniqueTmpDirectory();
        $this->ensureDirectoryExistsAndClear($tempDir.'/other');

        $finder = new Finder();
        $finder->files()->in($tempDir);

        file_put_contents($tempDir.'/A.php', "<?php\nclass A {}");
        file_put_contents($tempDir.'/other/A.php', "<?php\nclass A {}");

        $first  = realpath($tempDir.'/A.php');
        $second = realpath($tempDir.'/other/A.php');

        $messages = array(
            sprintf(
                '<warning>' .
                'Warning: Ambiguous class resolution, "A" was found in both "%s" and "%s", the first will be used.' .
                '</warning>',
                $first,
                $second
            ),
            sprintf(
                '<warning>' .
                'Warning: Ambiguous class resolution, "A" was found in both "%s" and "%s", the first will be used.' .
                '</warning>',
                $second,
                $first
            ),
        );

        $msgs = array();
        ClassMapGenerator::createMap($finder, null, null, $msgs);

        $this->assertCount(1, $msgs, 'Error message count should be 1.');

        $this->assertTrue(
            in_array($msgs[0], $messages, true),
            $msgs[0].' not found in expected messages ('.var_export($messages, true).')'
        );

        $filesystem = new Filesystem();
        $filesystem->remove($tempDir);
    }

    /**
     * If one file has a class or interface defined more than once,
     * an ambiguous reference warning should not be produced
     *
     * @return void
     */
    public function testUnambiguousReference()
    {
        $tempDir = $this->getUniqueTmpDirectory();

        file_put_contents($tempDir.'/A.php', "<?php\nclass A {}");
        file_put_contents(
            $tempDir.'/B.php',
            '<?php
                if (true) {
                    interface B {}
                } else {
                    interface B extends Iterator {}
                }
            '
        );

        foreach (array('test', 'fixture', 'example') as $keyword) {
            if (!is_dir($tempDir.'/'.$keyword)) {
                mkdir($tempDir.'/'.$keyword, 0777, true);
            }
            file_put_contents($tempDir.'/'.$keyword.'/A.php', "<?php\nclass A {}");
        }

        ClassMapGenerator::createMap($tempDir);

        $filesystem = new Filesystem();
        $filesystem->remove($tempDir);
    }

    /**
     * Test that an exception is thrown when directory does not exist.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     *
     * @expectedExceptionMessage Could not scan for classes inside
     */
    public function testCreateMapThrowsWhenDirectoryDoesNotExist()
    {
        ClassMapGenerator::createMap(__DIR__.'/no-file.no-foler');
    }

    /**
     * Assert that the passed values are equal after normalization.
     *
     * @param string[]    $expected Expected value.
     *
     * @param string[]    $actual   Actual value.
     *
     * @param null|string $message  Optional message.
     *
     * @return void
     */
    protected function assertEqualsNormalized($expected, $actual, $message = null)
    {
        foreach ($expected as $ns => $path) {
            $expected[$ns] = strtr($path, '\\', '/');
        }
        foreach ($actual as $ns => $path) {
            $actual[$ns] = strtr($path, '\\', '/');
        }
        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Create a unique temp directory.
     *
     * @return string
     *
     * @throws \RuntimeException When the directory could not be created.
     */
    public static function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root     = sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-test-' . rand(1000, 9000));

            if (!file_exists($unique) && mkdir($unique, 0777)) {
                return realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }

    /**
     * Ensure the directory exists and is empty.
     *
     * @param string $directory The directory to clean.
     *
     * @return void
     */
    protected static function ensureDirectoryExistsAndClear($directory)
    {
        $filesystem = new Filesystem();

        if (is_dir($directory)) {
            $filesystem->remove($directory);
        }

        mkdir($directory, 0777, true);
    }

    /**
     * Check if the finder class is available.
     *
     * @return void
     */
    private function checkIfFinderIsAvailable()
    {
        if (!class_exists('Symfony\\Component\\Finder\\Finder')) {
            $this->markTestSkipped('Finder component is not available');
        }
    }
}
