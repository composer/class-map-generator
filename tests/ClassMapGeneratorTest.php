<?php declare(strict_types=1);

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This file is copied from the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Composer\ClassMapGenerator;

use Composer\ClassMapGenerator\ClassMapGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class ClassMapGeneratorTest extends TestCase
{
    /**
     * @var ClassMapGenerator
     */
    private $generator;

    public function setUp(): void
    {
        parent::setUp();

        $this->generator = new ClassMapGenerator(['php', 'inc', 'hh']);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->generator);
    }

    /**
     * @dataProvider getTestCreateMapTests
     * @param string $directory
     * @param array<string, string> $expected
     */
    public function testCreateMap(string $directory, array $expected): void
    {
        self::assertEqualsNormalized($expected, ClassMapGenerator::createMap($directory));
    }

    /**
     * @return array<array<string|array<string>>>
     */
    public function getTestCreateMapTests(): array
    {
        $classmap = array(
            'Foo\\Bar\\A' => realpath(__DIR__) . '/Fixtures/classmap/sameNsMultipleClasses.php',
            'Foo\\Bar\\B' => realpath(__DIR__) . '/Fixtures/classmap/sameNsMultipleClasses.php',
            'Alpha\\A' => realpath(__DIR__) . '/Fixtures/classmap/multipleNs.php',
            'Alpha\\B' => realpath(__DIR__) . '/Fixtures/classmap/multipleNs.php',
            'A' => realpath(__DIR__) . '/Fixtures/classmap/multipleNs.php',
            'Be\\ta\\A' => realpath(__DIR__) . '/Fixtures/classmap/multipleNs.php',
            'Be\\ta\\B' => realpath(__DIR__) . '/Fixtures/classmap/multipleNs.php',
            'ClassMap\\SomeInterface' => realpath(__DIR__) . '/Fixtures/classmap/SomeInterface.php',
            'ClassMap\\SomeParent' => realpath(__DIR__) . '/Fixtures/classmap/SomeParent.php',
            'ClassMap\\SomeClass' => realpath(__DIR__) . '/Fixtures/classmap/SomeClass.php',
            'ClassMap\\LongString' => realpath(__DIR__) . '/Fixtures/classmap/LongString.php',
            'Foo\\LargeClass' => realpath(__DIR__) . '/Fixtures/classmap/LargeClass.php',
            'Foo\\LargeGap' => realpath(__DIR__) . '/Fixtures/classmap/LargeGap.php',
            'Foo\\MissingSpace' => realpath(__DIR__) . '/Fixtures/classmap/MissingSpace.php',
            'Foo\\StripNoise' => realpath(__DIR__) . '/Fixtures/classmap/StripNoise.php',
            'Foo\\First' => realpath(__DIR__) . '/Fixtures/classmap/StripNoise.php',
            'Foo\\Second' => realpath(__DIR__) . '/Fixtures/classmap/StripNoise.php',
            'Foo\\Third' => realpath(__DIR__) . '/Fixtures/classmap/StripNoise.php',
            'Foo\\SlashedA' => realpath(__DIR__) . '/Fixtures/classmap/BackslashLineEndingString.php',
            'Foo\\SlashedB' => realpath(__DIR__) . '/Fixtures/classmap/BackslashLineEndingString.php',
            'Unicode\\↑\\↑' => realpath(__DIR__) . '/Fixtures/classmap/Unicode.php',
            'ShortOpenTag' => realpath(__DIR__) . '/Fixtures/classmap/ShortOpenTag.php',
            'Smarty_Internal_Compile_Block' => realpath(__DIR__) . '/Fixtures/classmap/InvalidUnicode.php',
            'Smarty_Internal_Compile_Blockclose' => realpath(__DIR__) . '/Fixtures/classmap/InvalidUnicode.php',
            'ShortOpenTagDocblock' => realpath(__DIR__) . '/Fixtures/classmap/ShortOpenTagDocblock.php',
        );

        $data = array(
            array(__DIR__ . '/Fixtures/Namespaced', array(
                'Namespaced\\Bar' => realpath(__DIR__) . '/Fixtures/Namespaced/Bar.inc',
                'Namespaced\\Foo' => realpath(__DIR__) . '/Fixtures/Namespaced/Foo.php',
                'Namespaced\\Baz' => realpath(__DIR__) . '/Fixtures/Namespaced/Baz.php',
            )),
            array(__DIR__ . '/Fixtures/beta/NamespaceCollision', array(
                'NamespaceCollision\\A\\B\\Bar' => realpath(__DIR__) . '/Fixtures/beta/NamespaceCollision/A/B/Bar.php',
                'NamespaceCollision\\A\\B\\Foo' => realpath(__DIR__) . '/Fixtures/beta/NamespaceCollision/A/B/Foo.php',
            )),
            array(__DIR__ . '/Fixtures/Pearlike', array(
                'Pearlike_Foo' => realpath(__DIR__) . '/Fixtures/Pearlike/Foo.php',
                'Pearlike_Bar' => realpath(__DIR__) . '/Fixtures/Pearlike/Bar.php',
                'Pearlike_Baz' => realpath(__DIR__) . '/Fixtures/Pearlike/Baz.php',
            )),
            array(__DIR__ . '/Fixtures/classmap', $classmap),
            array(__DIR__ . '/Fixtures/template', array()),
        );

        $data[] = array(__DIR__ . '/Fixtures/php5.4', array(
            'TFoo' => __DIR__ . '/Fixtures/php5.4/traits.php',
            'CFoo' => __DIR__ . '/Fixtures/php5.4/traits.php',
            'Foo\\TBar' => __DIR__ . '/Fixtures/php5.4/traits.php',
            'Foo\\IBar' => __DIR__ . '/Fixtures/php5.4/traits.php',
            'Foo\\TFooBar' => __DIR__ . '/Fixtures/php5.4/traits.php',
            'Foo\\CBar' => __DIR__ . '/Fixtures/php5.4/traits.php',
        ));

        $data[] = array(__DIR__ . '/Fixtures/php7.0', array(
            'Dummy\Test\AnonClassHolder' => __DIR__ . '/Fixtures/php7.0/anonclass.php',
        ));

        if (PHP_VERSION_ID >= 80100) {
            $data[] = array(__DIR__ . '/Fixtures/php8.1', array(
                'RolesBasicEnum' => __DIR__ . '/Fixtures/php8.1/enum_basic.php',
                'RolesBackedEnum' => __DIR__ . '/Fixtures/php8.1/enum_backed.php',
                'RolesClassLikeEnum' => __DIR__ . '/Fixtures/php8.1/enum_class_semantics.php',
                'Foo\Bar\RolesClassLikeNamespacedEnum' => __DIR__ . '/Fixtures/php8.1/enum_namespaced.php',
            ));
        }

        if (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.3', '>=')) {
            $data[] = array(__DIR__ . '/Fixtures/hhvm3.3', array(
                'FooEnum' => __DIR__ . '/Fixtures/hhvm3.3/HackEnum.php',
                'Foo\BarEnum' => __DIR__ . '/Fixtures/hhvm3.3/NamespacedHackEnum.php',
                'GenericsClass' => __DIR__ . '/Fixtures/hhvm3.3/Generics.php',
            ));
        }

        return $data;
    }

    public function testCreateMapFinderSupport(): void
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__ . '/Fixtures/beta/NamespaceCollision');

        self::assertEqualsNormalized(array(
            'NamespaceCollision\\A\\B\\Bar' => realpath(__DIR__) . '/Fixtures/beta/NamespaceCollision/A/B/Bar.php',
            'NamespaceCollision\\A\\B\\Foo' => realpath(__DIR__) . '/Fixtures/beta/NamespaceCollision/A/B/Foo.php',
        ), ClassMapGenerator::createMap($finder));
    }

    public function testAmbiguousReference(): void
    {
        $tempDir = self::getUniqueTmpDirectory();
        mkdir($tempDir.'/other');

        $finder = new Finder();
        $finder->files()->in($tempDir);

        file_put_contents($tempDir . '/A.php', "<?php\nclass A {}");
        file_put_contents($tempDir . '/other/A.php', "<?php\nclass A {}");

        $a = realpath($tempDir . '/A.php');
        $b = realpath($tempDir . '/other/A.php');

        $possibleAmbiguousPaths = [$a, $b];

        $this->generator->scanPaths($finder);
        $classMap = $this->generator->getClassMap();
        self::assertCount(1, $classMap->getAmbiguousClasses());
        self::assertArrayHasKey('A', $classMap->getAmbiguousClasses());
        self::assertCount(1, $classMap->getAmbiguousClasses()['A']);
        $path = $classMap->getAmbiguousClasses()['A'][0];
        self::assertContains($path, $possibleAmbiguousPaths, $path . ' not found in expected paths (' . var_export($possibleAmbiguousPaths, true) . ')');

        $fs = new Filesystem();
        $fs->remove($tempDir);
    }

    /**
     * If one file has a class or interface defined more than once,
     * an ambiguous reference warning should not be produced
     */
    public function testUnambiguousReference(): void
    {
        $tempDir = self::getUniqueTmpDirectory();

        file_put_contents($tempDir . '/A.php', "<?php\nclass A {}");
        file_put_contents(
            $tempDir . '/B.php',
            "<?php
                if (true) {
                    interface B {}
                } else {
                    interface B extends Iterator {}
                }
            "
        );

        foreach (array('test', 'fixture', 'example') as $keyword) {
            if (!is_dir($tempDir . '/' . $keyword)) {
                mkdir($tempDir . '/' . $keyword, 0777, true);
            }
            file_put_contents($tempDir . '/' . $keyword . '/A.php', "<?php\nclass A {}");
        }

        $this->generator->scanPaths($tempDir);
        $classMap = $this->generator->getClassMap();

        self::assertCount(0, $classMap->getAmbiguousClasses());

        $fs = new Filesystem();
        $fs->remove($tempDir);
    }

    public function testCreateMapThrowsWhenDirectoryDoesNotExist(): void
    {
        self::expectException('RuntimeException');
        self::expectExceptionMessage('Could not scan for classes inside');
        ClassMapGenerator::createMap(__DIR__ . '/no-file.no-foler');
    }

    public function testCreateMapDoesNotHitRegexBacktraceLimit(): void
    {
        $expected = array(
            'Foo\\StripNoise' => realpath(__DIR__) . '/Fixtures/pcrebacktracelimit/StripNoise.php',
            'Foo\\VeryLongHeredoc' => realpath(__DIR__) . '/Fixtures/pcrebacktracelimit/VeryLongHeredoc.php',
            'Foo\\ClassAfterLongHereDoc' => realpath(__DIR__) . '/Fixtures/pcrebacktracelimit/VeryLongHeredoc.php',
            'Foo\\VeryLongPHP73Heredoc' => realpath(__DIR__) . '/Fixtures/pcrebacktracelimit/VeryLongPHP73Heredoc.php',
            'Foo\\VeryLongPHP73Nowdoc' => realpath(__DIR__) . '/Fixtures/pcrebacktracelimit/VeryLongPHP73Nowdoc.php',
            'Foo\\ClassAfterLongNowDoc' => realpath(__DIR__) . '/Fixtures/pcrebacktracelimit/VeryLongPHP73Nowdoc.php',
            'Foo\\VeryLongNowdoc' => realpath(__DIR__) . '/Fixtures/pcrebacktracelimit/VeryLongNowdoc.php',
        );

        ini_set('pcre.backtrack_limit', '30000');
        $result = ClassMapGenerator::createMap(__DIR__ . '/Fixtures/pcrebacktracelimit');
        ini_restore('pcre.backtrack_limit');

        self::assertEqualsNormalized($expected, $result);
    }

    /**
     * @param array<string, string> $expected
     * @param array<class-string, string> $actual
     * @param string $message
     * @return  void
     */
    protected static function assertEqualsNormalized(array $expected, array $actual, string $message = ''): void
    {
        foreach ($expected as $ns => $path) {
            $expected[$ns] = strtr($path, '\\', '/');
        }
        foreach ($actual as $ns => $path) {
            $actual[$ns] = strtr($path, '\\', '/');
        }
        self::assertEquals($expected, $actual, $message);
    }

    public static function getUniqueTmpDirectory(): string
    {
        $attempts = 5;
        $root = sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-test-' . random_int(1000, 9000));

            if (!file_exists($unique) && @mkdir($unique, 0777)) {
                return (string) realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }
}
