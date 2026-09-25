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

namespace Composer\ClassMapGenerator;

use PHPUnit\Framework\TestCase;

class ClassMapTest extends TestCase
{
    /**
     * @dataProvider provideAmbiguousFolders
     *
     * @param array<string, non-empty-string> $classes
     * @param list<non-empty-list<non-empty-string>> $expected
     */
    public function testGetAmbiguousFolders(array $classes, array $expected): void
    {
        self::assertSame($expected, self::classMapOf($classes)->getAmbiguousFolders(false));
    }

    /**
     * @return array<string, array{array<string, non-empty-string>, list<non-empty-list<non-empty-string>>}>
     */
    public static function provideAmbiguousFolders(): array
    {
        return [
            'empty map' => [
                [],
                [],
            ],
            'no ambiguity' => [
                [
                    'Foo\Bar' => '/proj/src/Foo/Bar.php',
                    'Foo\Baz' => '/proj/src/Foo/Baz.php',
                ],
                [],
            ],
            // the case this is all about: a folder renamed to a different casing, which merges
            // into a single folder once checked out on a case insensitive filesystem
            'folder differing in casing' => [
                [
                    'foo\Foo' => '/proj/src/foo/Foo.php',
                    'Foo\Bar' => '/proj/src/Foo/Bar.php',
                ],
                [
                    ['/proj/src/Foo', '/proj/src/foo'],
                ],
            ],
            'three variants of the same folder' => [
                [
                    'A\A' => '/proj/src/Casefolding/A.php',
                    'B\B' => '/proj/src/casefolding/B.php',
                    'C\C' => '/proj/src/CaseFolding/C.php',
                ],
                [
                    ['/proj/src/CaseFolding', '/proj/src/Casefolding', '/proj/src/casefolding'],
                ],
            ],
            // every level whose own name differs in casing is reported, as renaming the parent
            // does not make the children match each other
            'nested differences are all reported' => [
                [
                    'A\B' => '/proj/src/Foo/Bar/A.php',
                    'A\C' => '/proj/src/foo/bar/C.php',
                ],
                [
                    ['/proj/src/Foo', '/proj/src/foo'],
                    ['/proj/src/Foo/Bar', '/proj/src/foo/bar'],
                ],
            ],
            // a difference inherited from the parent is not repeated for children matching each other
            'ancestor difference is not repeated for identical children' => [
                [
                    'A\B' => '/proj/src/Foo/Bar/A.php',
                    'A\C' => '/proj/src/foo/Bar/B.php',
                ],
                [
                    ['/proj/src/Foo', '/proj/src/foo'],
                ],
            ],
            'ancestor difference is not repeated for identical files' => [
                [
                    'A\A' => '/proj/src/Foo/A.php',
                    'B\A' => '/proj/src/foo/A.php',
                ],
                [
                    ['/proj/src/Foo', '/proj/src/foo'],
                ],
            ],
            'files differing in casing under folders differing in casing' => [
                [
                    'A\A' => '/proj/src/Foo/A.php',
                    'B\B' => '/proj/src/foo/a.php',
                ],
                [
                    ['/proj/src/Foo', '/proj/src/foo'],
                    ['/proj/src/Foo/A.php', '/proj/src/foo/a.php'],
                ],
            ],
            'three variants under differently cased parents' => [
                [
                    'A\A' => '/proj/src/Foo/Bar/A.php',
                    'B\B' => '/proj/src/foo/bar/B.php',
                    'C\C' => '/proj/src/FOO/BAR/C.php',
                    'D\D' => '/proj/src/Foo/bar/D.php',
                ],
                [
                    ['/proj/src/FOO', '/proj/src/Foo', '/proj/src/foo'],
                    ['/proj/src/FOO/BAR', '/proj/src/Foo/Bar', '/proj/src/Foo/bar'],
                ],
            ],
            // Foo\Bar and Foo\bar are the same class as far as PHP is concerned, and the two files
            // cannot co-exist on a case insensitive filesystem either
            'files differing in casing' => [
                [
                    'Foo\Bar' => '/proj/src/Bar.php',
                    'Foo\bar' => '/proj/src/bar.php',
                ],
                [
                    ['/proj/src/Bar.php', '/proj/src/bar.php'],
                ],
            ],
            'folder and file with the same name' => [
                [
                    'A\A' => '/proj/src/Foo/A.php',
                    'B\B' => '/proj/src/foo.php',
                ],
                [],
            ],
            'unrelated folders in separate trees' => [
                [
                    'A\A' => '/proj/src/Foo/A.php',
                    'B\B' => '/proj/lib/foo/B.php',
                ],
                [],
            ],
            // a namespace legitimately spread over several folders is not a problem
            'namespace spanning several folders' => [
                [
                    'Foo\A' => '/proj/src/A.php',
                    'Foo\B' => '/proj/lib/B.php',
                ],
                [],
            ],
            // aws/aws-sdk-php uses the Aws namespace while its aws/aws-crt-php dependency uses AWS,
            // but they live in folders which do not fold into each other
            'namespaces differing in casing in separate folders' => [
                [
                    'AWS\CRT\CRT' => '/proj/vendor/aws/aws-crt-php/src/AWS/CRT/CRT.php',
                    'Aws\Sdk' => '/proj/vendor/aws/aws-sdk-php/src/Sdk.php',
                ],
                [],
            ],
            'windows directory separators' => [
                [
                    'A\A' => 'C:\\proj\\src\\Foo\\A.php',
                    'B\B' => 'C:\\proj\\src\\foo\\B.php',
                ],
                [
                    ['C:/proj/src/Foo', 'C:/proj/src/foo'],
                ],
            ],
            'mixed directory separators' => [
                [
                    'A\A' => 'C:\\proj\\src\\Foo\\A.php',
                    'B\B' => 'C:/proj/src/foo/B.php',
                ],
                [
                    ['C:/proj/src/Foo', 'C:/proj/src/foo'],
                ],
            ],
            // drive letters and stream wrapper schemes are never case sensitive
            'drive letter casing is ignored' => [
                [
                    'A\A' => 'C:\\proj\\src\\A.php',
                    'B\B' => 'c:\\proj\\src\\B.php',
                ],
                [],
            ],
            'drive letter casing is kept in reported paths' => [
                [
                    'A\A' => 'C:\\proj\\src\\Foo\\A.php',
                    'B\B' => 'c:\\proj\\src\\foo\\B.php',
                ],
                [
                    ['C:/proj/src/Foo', 'c:/proj/src/foo'],
                ],
            ],
            'stream wrapper scheme casing is ignored' => [
                [
                    'A\A' => 'phar:///proj/lib.phar/src/A.php',
                    'B\B' => 'PHAR:///proj/lib.phar/src/B.php',
                ],
                [],
            ],
            'drive letter under a stream wrapper' => [
                [
                    'A\A' => 'phar://C:/proj/lib.phar/src/A.php',
                    'B\B' => 'phar://c:/proj/lib.phar/src/B.php',
                ],
                [],
            ],
            'relative paths' => [
                [
                    'A\A' => 'src/Foo/A.php',
                    'B\B' => 'src/foo/B.php',
                ],
                [
                    ['src/Foo', 'src/foo'],
                ],
            ],
            'several ambiguous folders' => [
                [
                    'A\A' => '/proj/src/Zed/A.php',
                    'B\B' => '/proj/src/zed/B.php',
                    'C\C' => '/proj/src/Abc/C.php',
                    'D\D' => '/proj/src/abc/D.php',
                ],
                [
                    ['/proj/src/Abc', '/proj/src/abc'],
                    ['/proj/src/Zed', '/proj/src/zed'],
                ],
            ],
        ];
    }

    public function testGetAmbiguousFoldersIsNotAffectedByInsertionOrder(): void
    {
        $classes = [
            'A\A' => '/proj/src/Foo/Bar/A.php',
            'B\B' => '/proj/src/foo/B.php',
            'C\C' => '/proj/src/Foo/bar/C.php',
            'D\D' => '/proj/src/foo/Bar/D.php',
        ];
        // src/foo/Bar only differs from src/Foo/Bar by its parent so it is not listed
        $expected = [
            ['/proj/src/Foo', '/proj/src/foo'],
            ['/proj/src/Foo/Bar', '/proj/src/Foo/bar'],
        ];

        foreach (self::permutations(array_keys($classes)) as $order) {
            $permutation = [];
            foreach ($order as $class) {
                $permutation[$class] = $classes[$class];
            }

            self::assertSame($expected, self::classMapOf($permutation)->getAmbiguousFolders(false), 'Insertion order: '.implode(', ', $order));
        }
    }

    public function testGetAmbiguousFoldersIncludesPathsOfAmbiguousClasses(): void
    {
        // a folder renamed to a different casing with stale copies left behind: the same classes
        // are found in both, so the second copy only ends up in the ambiguous classes
        $classMap = self::classMapOf(
            [
                'Foo\A' => '/proj/src/Foo/A.php',
                'Foo\B' => '/proj/src/Foo/B.php',
            ],
            [
                'Foo\A' => ['/proj/src/foo/A.php'],
                'Foo\B' => ['/proj/src/foo/B.php'],
            ]
        );

        $expected = [['/proj/src/Foo', '/proj/src/foo']];
        self::assertSame($expected, $classMap->getAmbiguousFolders());
        self::assertSame($expected, $classMap->getAmbiguousFolders(false));
    }

    public function testGetAmbiguousFoldersFiltersTestPathsByDefault(): void
    {
        $classMap = self::classMapOf([
            'A\A' => '/proj/tests/Foo/A.php',
            'B\B' => '/proj/tests/foo/B.php',
        ]);

        self::assertSame([], $classMap->getAmbiguousFolders());
        self::assertSame([['/proj/tests/Foo', '/proj/tests/foo']], $classMap->getAmbiguousFolders(false));
    }

    public function testGetAmbiguousFoldersOnlyFiltersFoldersWithoutUnfilteredPaths(): void
    {
        // a test folder which merges with a production folder is still a problem for the latter
        $classMap = self::classMapOf([
            'A\A' => '/proj/src/Foo/A.php',
            'B\B' => '/proj/src/foo/Tests/B.php',
            'C\C' => '/proj/src/foo/tests/C.php',
        ]);

        self::assertSame([['/proj/src/Foo', '/proj/src/foo']], $classMap->getAmbiguousFolders());
        self::assertSame([
            ['/proj/src/Foo', '/proj/src/foo'],
            ['/proj/src/foo/Tests', '/proj/src/foo/tests'],
        ], $classMap->getAmbiguousFolders(false));

        // while folders only containing filtered paths are ignored altogether
        $classMap = self::classMapOf([
            'A\A' => '/proj/tests/Foo/A.php',
            'B\B' => '/proj/Tests/foo/B.php',
        ]);

        self::assertSame([], $classMap->getAmbiguousFolders());
    }

    public function testDefaultDuplicatesFilterAppliesToRelativePaths(): void
    {
        $classMap = self::classMapOf([
            'A\A' => 'tests/Foo/A.php',
            'B\B' => 'tests/foo/B.php',
        ]);

        self::assertSame([], $classMap->getAmbiguousFolders());
        self::assertSame([['tests/Foo', 'tests/foo']], $classMap->getAmbiguousFolders(false));

        $classMap = self::classMapOf(['A' => 'src/A.php'], ['A' => ['tests/A.php']]);

        self::assertSame([], $classMap->getAmbiguousClasses());
        self::assertSame(['A' => ['tests/A.php']], $classMap->getAmbiguousClasses(false));
    }

    public function testGetAmbiguousFoldersAcceptsACustomFilter(): void
    {
        $classMap = self::classMapOf([
            'A\A' => '/proj/generated/Foo/A.php',
            'B\B' => '/proj/generated/foo/B.php',
        ]);

        self::assertSame([], $classMap->getAmbiguousFolders('{/generated/}'));
        self::assertSame([['/proj/generated/Foo', '/proj/generated/foo']], $classMap->getAmbiguousFolders());
    }

    public function testGetAmbiguousFoldersIsRecomputedWhenClassesAreAdded(): void
    {
        $classMap = self::classMapOf(['A\\A' => '/proj/src/Foo/A.php']);
        self::assertSame([], $classMap->getAmbiguousFolders());

        /** @phpstan-ignore argument.type */
        $classMap->addClass('B\\B', '/proj/src/foo/B.php');
        self::assertSame([['/proj/src/Foo', '/proj/src/foo']], $classMap->getAmbiguousFolders());

        /** @phpstan-ignore argument.type */
        $classMap->addAmbiguousClass('B\\B', '/proj/src/FOO/B.php');
        self::assertSame([['/proj/src/FOO', '/proj/src/Foo', '/proj/src/foo']], $classMap->getAmbiguousFolders());
        // the unfiltered result is cached separately
        self::assertSame([['/proj/src/FOO', '/proj/src/Foo', '/proj/src/foo']], $classMap->getAmbiguousFolders(false));
    }

    public function testGetAmbiguousFoldersRejectsTrueAsFilter(): void
    {
        self::expectException(\InvalidArgumentException::class);

        // @phpstan-ignore argument.type
        self::classMapOf([])->getAmbiguousFolders(true);
    }

    /**
     * @param array<string, non-empty-string> $classes
     * @param array<string, non-empty-list<non-empty-string>> $ambiguousClasses
     */
    private static function classMapOf(array $classes, array $ambiguousClasses = []): ClassMap
    {
        $classMap = new ClassMap;
        foreach ($classes as $class => $path) {
            /** @phpstan-ignore argument.type */
            $classMap->addClass($class, $path);
        }
        foreach ($ambiguousClasses as $class => $paths) {
            foreach ($paths as $path) {
                /** @phpstan-ignore argument.type */
                $classMap->addAmbiguousClass($class, $path);
            }
        }

        return $classMap;
    }

    /**
     * @template T
     * @param list<T> $items
     * @return list<list<T>>
     */
    private static function permutations(array $items): array
    {
        if (\count($items) <= 1) {
            return [$items];
        }

        $permutations = [];
        foreach ($items as $i => $item) {
            $rest = $items;
            unset($rest[$i]);
            foreach (self::permutations(array_values($rest)) as $permutation) {
                array_unshift($permutation, $item);
                $permutations[] = $permutation;
            }
        }

        return $permutations;
    }
}
