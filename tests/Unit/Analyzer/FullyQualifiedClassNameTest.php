<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FullyQualifiedClassName;
use PHPUnit\Framework\TestCase;

class FullyQualifiedClassNameTest extends TestCase
{
    public function patternProvider(): array
    {
        return [
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\Fruits\Banana', true],
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\*\Banana', true],
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables', true],
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\*', true],
          ['Food\Vegetables\Fruits\Mango', '', false],
        ];
    }

    /**
     * @dataProvider patternProvider
     */
    public function testShouldMatchNamespacesWithWildcards(string $fqcn, string $pattern, bool $shouldMatch): void
    {
        $fqcn = FullyQualifiedClassName::fromString($fqcn);

        $this->assertEquals($shouldMatch, $fqcn->matches($pattern));
    }

    public function testShouldThrowIfInvalidNamespaceIsPassed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('-Gvnn is not a valid namespace definition');

        FullyQualifiedClassName::fromString('-Gvnn');
    }

    public function testShouldReturnClassName(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');

        $this->assertEquals('Banana', $fqcn->className());
    }
}
