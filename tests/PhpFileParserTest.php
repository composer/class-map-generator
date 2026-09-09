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

use Composer\Pcre\Preg;
use PHPUnit\Framework\TestCase;

class PhpFileParserTest extends TestCase
{
    public function testFindClassesThrowsWhenFileDoesNotExist(): void
    {
        self::expectException('RuntimeException');
        self::expectExceptionMessage('does not exist');
        PhpFileParser::findClasses(__DIR__ . '/no-file');
    }

    /**
     * The raw target token after "on" is captured as written (relative names and import
     * aliases stay unresolved), in anticipation of the target-hint optimization from the
     * Extension Methods RFC's Future Scope. It is not part of the class map output.
     *
     * @dataProvider extensionTargetProvider
     * @param array<string, string> $expected extension name => raw target token
     */
    public function testExtensionTargetIsCapturedRaw(string $file, array $expected): void
    {
        PhpFileCleaner::setTypeConfig(['class', 'interface', 'trait', 'extension', 'enum']);

        $contents = php_strip_whitespace($file);
        // maxMatches > 1 disables the cleaner's single-match early return so the whole file is cleaned
        $cleaner = new PhpFileCleaner($contents, PHP_INT_MAX);
        $contents = $cleaner->clean();

        Preg::matchAllStrictGroups('{'.PhpFileParser::EXTENSION_REGEX.'}ix', $contents, $matches);

        $actual = [];
        foreach ($matches['extname'] as $i => $name) {
            $actual[$name] = $matches['exttarget'][$i];
        }

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function extensionTargetProvider(): array
    {
        return [
            'fully-qualified target' => [__DIR__ . '/Fixtures/extensions/ExtensionGlobal.php', [
                'DomTraversal' => '\DOMElement',
            ]],
            'fully-qualified target in namespace' => [__DIR__ . '/Fixtures/extensions/ExtensionNamespaced.php', [
                'DomTraversal' => '\DOMElement',
            ]],
            'scalar and array targets' => [__DIR__ . '/Fixtures/extensions/ExtensionScalarTargets.php', [
                'StringHelpers' => 'string',
                'ArrayHelpers' => 'array',
            ]],
            'relative qualified target stays unresolved' => [__DIR__ . '/Fixtures/extensions/ExtensionMultiline.php', [
                'WidgetHelpers' => '\Acme\Widgets\Widget',
                'CaseInsensitive' => 'Widgets\Widget',
            ]],
            'aliased target stays unresolved' => [__DIR__ . '/Fixtures/extensions/ExtensionAliasedTarget.php', [
                'WidgetShortcuts' => 'W',
            ]],
            'unqualified target' => [__DIR__ . '/Fixtures/extensions/ExtensionWithClass.php', [
                'FormatterHelpers' => 'Formatter',
            ]],
            'anonymous forms capture nothing' => [__DIR__ . '/Fixtures/extensions/ExtensionAnonymous.php', []],
            'false positives capture nothing' => [__DIR__ . '/Fixtures/extensions/ExtensionFalsePositives.php', []],
        ];
    }
}
