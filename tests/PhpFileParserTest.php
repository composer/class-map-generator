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

class PhpFileParserTest extends TestCase
{
    /**
     * @dataProvider provideRawSourceFiles
     * @param list<string> $expected
     */
    public function testRawSourcePreservesClassExtraction(string $file, array $expected): void
    {
        self::assertSame($expected, PhpFileParser::findClasses(__DIR__ . '/Fixtures/raw-source/' . $file));
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function provideRawSourceFiles(): array
    {
        return [
            'hash comments' => ['hash-comments.php', ['HashComments\Real']],
            'hash comment ending at a close tag' => ['hash-close-tag.php', ['HashClose\Real']],
            'slash comment ending at a close tag' => ['slash-close-tag.php', ['SlashClose\Real']],
            'attributes' => ['attributes.php', ['Attributes\Real']],
            'comment-spliced keyword' => ['comment-spliced-keyword.php', ['Spliced\Real']],
            'docblock class names' => ['docblock.php', ['Docblock\Real']],
            'empty file' => ['empty.php', []],
            // Shell (backtick) strings are not skipped by the cleaner yet.
            'hash inside a shell string' => ['shell-hash.php', ['ShellHash\Fake', 'ShellHash\Real']],
            'close tag inside a shell string' => ['shell-close-tag.php', []],
        ];
    }

    public function testFindClassesThrowsWhenFileDoesNotExist(): void
    {
        self::expectException('RuntimeException');
        self::expectExceptionMessage('does not exist');
        PhpFileParser::findClasses(__DIR__ . '/no-file');
    }
}
