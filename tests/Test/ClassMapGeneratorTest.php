<?php

/**
 * This file is part of phpcq/autoload-validation.
 *
 * (c) 2014-2020 Christian Schiffler, Tristan Lins
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    phpcq/autoload-validation
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2014-2020 Christian Schiffler <c.schiffler@cyberspectrum.de>
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
 *
 * @covers \PhpCodeQuality\AutoloadValidation\ClassMapGenerator
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
        $fixtures = \dirname(\realpath(__DIR__)) . '/fixtures/classmap';

        $data = [
            [
                $fixtures . '/Namespaced', [
                'Namespaced\\Bar' => $fixtures . '/Namespaced/Bar.inc',
                'Namespaced\\Foo' => $fixtures . '/Namespaced/Foo.php',
                'Namespaced\\Baz' => $fixtures . '/Namespaced/Baz.php',
            ]
            ],
            [
                $fixtures . '/beta/NamespaceCollision', [
                'NamespaceCollision\\A\\B\\Bar' => $fixtures . '/beta/NamespaceCollision/A/B/Bar.php',
                'NamespaceCollision\\A\\B\\Foo' => $fixtures . '/beta/NamespaceCollision/A/B/Foo.php',
            ]
            ],
            [
                $fixtures . '/Pearlike', [
                'Pearlike_Foo' => $fixtures . '/Pearlike/Foo.php',
                'Pearlike_Bar' => $fixtures . '/Pearlike/Bar.php',
                'Pearlike_Baz' => $fixtures . '/Pearlike/Baz.php',
            ]
            ],
            [
                $fixtures . '/classmap', [
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
            ]
            ],
            [\dirname(\realpath(__DIR__)) . '/fixtures/classmap/template', []],
        ];

        if (PHP_VERSION_ID >= 50400) {
            $data[] = [
                $fixtures . '/php5.4', [
                'TFoo'         => $fixtures . '/php5.4/traits.php',
                'CFoo'         => $fixtures . '/php5.4/traits.php',
                'Foo\\TBar'    => $fixtures . '/php5.4/traits.php',
                'Foo\\IBar'    => $fixtures . '/php5.4/traits.php',
                'Foo\\TFooBar' => $fixtures . '/php5.4/traits.php',
                'Foo\\CBar'    => $fixtures . '/php5.4/traits.php',
                ]
            ];
        }
        if (PHP_VERSION_ID >= 70000) {
            $data[] = [
                $fixtures . '/php7.0', [
                'Dummy\Test\AnonClassHolder' => $fixtures . '/php7.0/anonclass.php',
                ]
            ];
        }
        if (\defined('HHVM_VERSION') && \version_compare(HHVM_VERSION, '3.3', '>=')) {
            $data[] = [
                $fixtures . '/hhvm3.3', [
                'FooEnum'       => $fixtures . '/hhvm3.3/HackEnum.php',
                'Foo\BarEnum'   => $fixtures . '/hhvm3.3/NamespacedHackEnum.php',
                'GenericsClass' => $fixtures . '/hhvm3.3/Generics.php',
                ]
            ];
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

        $fixtures = \dirname(\realpath(__DIR__)) . '/fixtures/classmap';
        $finder   = new Finder();
        $finder->files()->in($fixtures . '/beta/NamespaceCollision');

        $this->assertEqualsNormalized(
            [
            'NamespaceCollision\\A\\B\\Bar' => $fixtures . '/beta/NamespaceCollision/A/B/Bar.php',
            'NamespaceCollision\\A\\B\\Foo' => $fixtures . '/beta/NamespaceCollision/A/B/Foo.php',
            ],
            ClassMapGenerator::createMap($finder)
        );
    }

    /**
     * Test that find classes throws an exception when none are found.
     *
     * @return void
     */
    public function testFindClassesThrowsWhenFileDoesNotExist()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\RuntimeException::class);
        } else {
            $this->setExpectedException(\RuntimeException::class);
        }

        $refl = new \ReflectionClass(ClassMapGenerator::class);
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

        $tempDir = self::getUniqueTmpDirectory();
        self::ensureDirectoryExistsAndClear($tempDir . '/other');

        $finder = new Finder();
        $finder->files()->in($tempDir);

        \file_put_contents($tempDir.'/A.php', "<?php\nclass A {}");
        \file_put_contents($tempDir.'/other/A.php', "<?php\nclass A {}");

        $first  = \realpath($tempDir.'/A.php');
        $second = \realpath($tempDir.'/other/A.php');

        $messages = [
            \sprintf(
                '<warning>' .
                'Warning: Ambiguous class resolution, "A" was found in both "%s" and "%s", the first will be used.' .
                '</warning>',
                $first,
                $second
            ),
            \sprintf(
                '<warning>' .
                'Warning: Ambiguous class resolution, "A" was found in both "%s" and "%s", the first will be used.' .
                '</warning>',
                $second,
                $first
            ),
        ];

        $msgs = [];
        ClassMapGenerator::createMap($finder, null, null, $msgs);

        self::assertCount(1, $msgs, 'Error message count should be 1.');

        self::assertContains(
            $msgs[0],
            $messages,
            $msgs[0] . ' not found in expected messages (' . \var_export($messages, true) . ')'
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
        $tempDir = self::getUniqueTmpDirectory();

        \file_put_contents($tempDir.'/A.php', "<?php\nclass A {}");
        \file_put_contents(
            $tempDir.'/B.php',
            '<?php
                if (true) {
                    interface B {}
                } else {
                    interface B extends Iterator {}
                }
            '
        );

        foreach (['test', 'fixture', 'example'] as $keyword) {
            if (!\is_dir($tempDir.'/'.$keyword)) {
                \mkdir($tempDir.'/'.$keyword, 0777, true);
            }
            \file_put_contents($tempDir.'/'.$keyword.'/A.php', "<?php\nclass A {}");
        }

        ClassMapGenerator::createMap($tempDir);
        $this->addToAssertionCount(1);

        $filesystem = new Filesystem();
        $filesystem->remove($tempDir);
    }

    /**
     * Test that an exception is thrown when directory does not exist.
     *
     * @return void
     */
    public function testCreateMapThrowsWhenDirectoryDoesNotExist()
    {
        if (70000 < PHP_VERSION_ID) {
            $this->expectException(\RuntimeException::class);
        } else {
            $this->setExpectedException(\RuntimeException::class);
        }

        ClassMapGenerator::createMap(__DIR__.'/no-file.no-foler');
    }

    /**
     * Assert that the passed values are equal after normalization.
     *
     * @param string[] $expected Expected value.
     *
     * @param string[] $actual   Actual value.
     *
     * @param string   $message  Optional message.
     *
     * @return void
     */
    protected function assertEqualsNormalized($expected, $actual, $message = '')
    {
        foreach ($expected as $ns => $path) {
            $expected[$ns] = \str_replace('\\', '/', $path);
        }
        foreach ($actual as $ns => $path) {
            $actual[$ns] = \str_replace('\\', '/', $path);
        }
        self::assertEquals($expected, $actual, $message);
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
        $root     = \sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . \uniqid('composer-test-' . \rand(1000, 9000));

            if (!\file_exists($unique) && \mkdir($unique, 0777)) {
                return \realpath($unique);
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

        if (\is_dir($directory)) {
            $filesystem->remove($directory);
        }

        \mkdir($directory, 0777, true);
    }

    /**
     * Check if the finder class is available.
     *
     * @return void
     */
    private function checkIfFinderIsAvailable()
    {
        if (!\class_exists(Finder::class)) {
            self::markTestSkipped('Finder component is not available');
        }
    }
}
