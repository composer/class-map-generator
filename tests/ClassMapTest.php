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
            // only the topmost difference is reported, renaming it resolves the ones below it
            'nested differences are reported once' => [
                [
                    'A\B' => '/proj/src/Foo/Bar/A.php',
                    'A\C' => '/proj/src/foo/bar/C.php',
                ],
                [
                    ['/proj/src/Foo', '/proj/src/foo'],
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
            'A\A' => '/proj/src/Casefolding/A.php',
            'B\B' => '/proj/src/casefolding/B.php',
        ];

        self::assertSame(
            self::classMapOf($classes)->getAmbiguousFolders(false),
            self::classMapOf(array_reverse($classes, true))->getAmbiguousFolders(false)
        );
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

    public function testGetAmbiguousFoldersAcceptsACustomFilter(): void
    {
        $classMap = self::classMapOf([
            'A\A' => '/proj/generated/Foo/A.php',
            'B\B' => '/proj/generated/foo/B.php',
        ]);

        self::assertSame([], $classMap->getAmbiguousFolders('{/generated/}'));
        self::assertSame([['/proj/generated/Foo', '/proj/generated/foo']], $classMap->getAmbiguousFolders());
    }

    public function testGetAmbiguousFoldersRejectsTrueAsFilter(): void
    {
        self::expectException(\InvalidArgumentException::class);

        // @phpstan-ignore argument.type
        self::classMapOf([])->getAmbiguousFolders(true);
    }

    /**
     * @param array<string, non-empty-string> $classes
     */
    private static function classMapOf(array $classes): ClassMap
    {
        $classMap = new ClassMap;
        foreach ($classes as $class => $path) {
            /** @phpstan-ignore argument.type */
            $classMap->addClass($class, $path);
        }

        return $classMap;
    }
}
