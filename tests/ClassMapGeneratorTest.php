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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class ClassMapGeneratorTest extends TestCase
{
    /**
     * @var ClassMapGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ClassMapGenerator(['php', 'inc', 'hh']);
    }

    protected function tearDown(): void
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

    /**
     * @see ClassMapGenerator::isStreamWrapper()
     */
    public function testStreamWrapperSupport(): void {

        /**
         * A stream wrapper that given `test://myfile.php` will read `test://path/to/myfile.php` where `path/to` is
         * set on the `$rootPath` static variable.
         */
        $testProxyStreamWrapper = new class() {
            /**
             * @var string
             */
            public static $rootPath;

            /**
             * @var string
             */
            protected $real;

            /**
             * @var resource|false
             */
            protected $resource;

            /**
             * @param string $path
             * @param string $mode
             * @param int $options
             * @param ?string $opened_path
             *
             * @return bool
             */
            public function stream_open($path, $mode, $options, &$opened_path) {
                $scheme  = parse_url($path, PHP_URL_SCHEME);
                $varname = str_replace($scheme . '://', '', $path);

                $this->real = 'file://' . self::$rootPath . '/' . $varname;

                $this->resource = fopen($this->real, $mode);

                return (bool) $this->resource;
            }

            /**
             * @param int<0, max>|null $count
             *
             * @return false|string
             */
            public function stream_read($count) {
                return $this->resource === false ? false : fgets($this->resource, (int) $count);
            }

            /**
             * @return array<int|string, int>|false
             */
            public function stream_stat() {
                return $this->resource === false ? false : fstat($this->resource);
            }
        };

        $testProxyStreamWrapper::$rootPath = realpath(__DIR__) . '/Fixtures/classmap';
        stream_wrapper_register('test', get_class($testProxyStreamWrapper));

        $arrayOfSplFileInfoStreamPaths = [
            new \SplFileInfo('test://BackslashLineEndingString.php'),
            new \SplFileInfo('test://InvalidUnicode.php'),
        ];

        self::assertSame(
            [
                'Foo\\SlashedA'                      => 'test://BackslashLineEndingString.php',
                'Foo\\SlashedB'                      => 'test://BackslashLineEndingString.php',
                'Smarty_Internal_Compile_Block'      => 'test://InvalidUnicode.php',
                'Smarty_Internal_Compile_Blockclose' => 'test://InvalidUnicode.php',
            ],
            ClassMapGenerator::createMap($arrayOfSplFileInfoStreamPaths)
        );
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

        mkdir($tempDir.'/src');
        mkdir($tempDir.'/ambiguous');
        file_put_contents($tempDir . '/src/A.php', "<?php\nclass A {}");
        file_put_contents(
            $tempDir . '/src/B.php',
            "<?php
                if (true) {
                    interface B {}
                } else {
                    interface B extends Iterator {}
                }
            "
        );

        foreach (array('test', 'fixture', 'example') as $keyword) {
            if (!is_dir($tempDir . '/ambiguous/' . $keyword)) {
                mkdir($tempDir . '/ambiguous/' . $keyword, 0777, true);
            }
            file_put_contents($tempDir . '/ambiguous/' . $keyword . '/A.php', "<?php\nclass A {}");
        }

        // if we scan src first, then test ambiguous refs will be ignored correctly
        $this->generator->scanPaths($tempDir.'/src');
        $this->generator->scanPaths($tempDir.'/ambiguous');
        $classMap = $this->generator->getClassMap();
        self::assertCount(0, $classMap->getAmbiguousClasses());

        // but when retrieving without filtering, the ambiguous classes are there
        self::assertCount(1, $classMap->getAmbiguousClasses(false));
        self::assertCount(3, $classMap->getAmbiguousClasses(false)['A']);

        // if we scan tests first however, then we always get ambiguous refs as the test one is overriding src
        $this->generator = new ClassMapGenerator(['php', 'inc', 'hh']);
        $this->generator->scanPaths($tempDir.'/ambiguous');
        $this->generator->scanPaths($tempDir.'/src');
        $classMap = $this->generator->getClassMap();

        // when retrieving with filtering, only the one from src is seen as ambiguous
        self::assertCount(1, $classMap->getAmbiguousClasses());
        self::assertCount(1, $classMap->getAmbiguousClasses()['A']);
        self::assertSame($tempDir.'/src'.DIRECTORY_SEPARATOR.'A.php', $classMap->getAmbiguousClasses()['A'][0]);
        // when retrieving without filtering, all the ambiguous classes are there
        self::assertCount(1, $classMap->getAmbiguousClasses(false));
        self::assertCount(3, $classMap->getAmbiguousClasses(false)['A']);

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

    public function testGetPSR4Violations(): void
    {
        $this->generator->scanPaths(__DIR__ . '/Fixtures/psrViolations', null, 'psr-4', 'ExpectedNamespace\\');
        $classMap = $this->generator->getClassMap();
        $violations = $classMap->getPsrViolations();
        sort($violations);
        self::assertSame(
            [
                'Class ClassWithoutNameSpace located in ./tests/Fixtures/psrViolations/ClassWithoutNameSpace.php does not comply with psr-4 autoloading standard (rule: ExpectedNamespace\ => ./tests/Fixtures/psrViolations). Skipping.',
                'Class ExpectedNamespace\UnexpectedSubNamespace\ClassWithIncorrectSubNamespace located in ./tests/Fixtures/psrViolations/ClassWithIncorrectSubNamespace.php does not comply with psr-4 autoloading standard (rule: ExpectedNamespace\ => ./tests/Fixtures/psrViolations). Skipping.',
                'Class UnexpectedNamespace\ClassWithNameSpaceOutsideConfiguredScope located in ./tests/Fixtures/psrViolations/ClassWithNameSpaceOutsideConfiguredScope.php does not comply with psr-4 autoloading standard (rule: ExpectedNamespace\ => ./tests/Fixtures/psrViolations). Skipping.',
            ],
            $violations
        );
    }

    public function testGetRawPSR4Violations(): void
    {
        $this->generator->scanPaths(__DIR__ . '/Fixtures/psrViolations', null, 'psr-4', 'ExpectedNamespace\\');
        $classMap = $this->generator->getClassMap();
        $rawViolations = $classMap->getRawPsrViolations();

        $classWithoutNameSpaceFilepath = strtr(__DIR__, '\\', '/') . '/Fixtures/psrViolations/ClassWithoutNameSpace.php';
        $classWithIncorrectSubNamespaceFilepath = strtr(__DIR__, '\\', '/') . '/Fixtures/psrViolations/ClassWithIncorrectSubNamespace.php';
        $classWithNameSpaceOutsideConfiguredScopeFilepath = strtr(__DIR__, '\\', '/') . '/Fixtures/psrViolations/ClassWithNameSpaceOutsideConfiguredScope.php';

        self::assertArrayHasKey($classWithoutNameSpaceFilepath, $rawViolations);
        self::assertCount(1, $rawViolations[$classWithoutNameSpaceFilepath]);
        self::assertSame('Class ClassWithoutNameSpace located in ./tests/Fixtures/psrViolations/ClassWithoutNameSpace.php does not comply with psr-4 autoloading standard (rule: ExpectedNamespace\ => ./tests/Fixtures/psrViolations). Skipping.', $rawViolations[$classWithoutNameSpaceFilepath][0]['warning']);
        self::assertSame('ClassWithoutNameSpace', $rawViolations[$classWithoutNameSpaceFilepath][0]['className']);

        self::assertArrayHasKey($classWithIncorrectSubNamespaceFilepath, $rawViolations);
        self::assertCount(1, $rawViolations[$classWithIncorrectSubNamespaceFilepath]);
        self::assertSame('Class ExpectedNamespace\UnexpectedSubNamespace\ClassWithIncorrectSubNamespace located in ./tests/Fixtures/psrViolations/ClassWithIncorrectSubNamespace.php does not comply with psr-4 autoloading standard (rule: ExpectedNamespace\ => ./tests/Fixtures/psrViolations). Skipping.', $rawViolations[$classWithIncorrectSubNamespaceFilepath][0]['warning']);
        self::assertSame('ExpectedNamespace\UnexpectedSubNamespace\ClassWithIncorrectSubNamespace', $rawViolations[$classWithIncorrectSubNamespaceFilepath][0]['className']);

        self::assertArrayHasKey($classWithNameSpaceOutsideConfiguredScopeFilepath, $rawViolations);
        self::assertCount(1, $rawViolations[$classWithNameSpaceOutsideConfiguredScopeFilepath]);
        self::assertSame('Class UnexpectedNamespace\ClassWithNameSpaceOutsideConfiguredScope located in ./tests/Fixtures/psrViolations/ClassWithNameSpaceOutsideConfiguredScope.php does not comply with psr-4 autoloading standard (rule: ExpectedNamespace\ => ./tests/Fixtures/psrViolations). Skipping.', $rawViolations[$classWithNameSpaceOutsideConfiguredScopeFilepath][0]['warning']);
        self::assertSame('UnexpectedNamespace\ClassWithNameSpaceOutsideConfiguredScope', $rawViolations[$classWithNameSpaceOutsideConfiguredScopeFilepath][0]['className']);
    }

    public function testCreateMapWithDirectoryExcluded(): void
    {
        $expected = array(
            'PrefixCollision_A_B_Bar' => realpath(__DIR__) . '/Fixtures/beta/PrefixCollision/A/B/Bar.php',
            'PrefixCollision_A_B_Foo' => realpath(__DIR__) . '/Fixtures/beta/PrefixCollision/A/B/Foo.php',
        );

        $this->generator->scanPaths(realpath(__DIR__) . '/Fixtures/beta', null, 'classmap', null, ['NamespaceCollision']);
        $result = $this->generator->getClassMap();
        self::assertEqualsNormalized($expected, $result->getMap());
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
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-classmap-' . random_int(1000, 9000));

            if (!file_exists($unique) && @mkdir($unique, 0777)) {
                return (string) realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }
}
