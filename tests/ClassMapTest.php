<?php declare(strict_types=1);

namespace Composer\ClassMapGenerator;

use PHPUnit\Framework\TestCase;

class ClassMapTest extends TestCase
{
    public function testAmbiguousNamespace(): void
    {
        $classMap = new ClassMap();

        /** @phpstan-ignore argument.type */
        $classMap->addClass('Foo\casefolding\Foo', './casefolding');

        /** @phpstan-ignore argument.type */
        $classMap->addClass('Foo\Casefolding\Bar', './Casefolding');

        /** @phpstan-ignore argument.type */
        $classMap->addClass('Foo\Casefolding\Boop', './Casefolding');

        self::assertSame(
            [
                ['Foo\casefolding', 'Foo\Casefolding'],
            ],
            $classMap->getAmbiguousNamespaces()
        );
    }
}
