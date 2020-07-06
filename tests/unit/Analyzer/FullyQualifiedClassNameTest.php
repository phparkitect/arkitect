<?php
declare(strict_types=1);

namespace ArkitectTests\Analyzer;

use Arkitect\Analyzer\FullyQualifiedClassName;
use PHPUnit\Framework\TestCase;

class FullyQualifiedClassNameTest extends TestCase
{
    public function patternProvider(): array
    {
        return [
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\Fruits\Banana', true],
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\*\Banana', true],
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\**', true],
          ['Food\Vegetables\Fruits\Banana', 'Food\**\Banana', true],
          ['Food\Vegetables\Fruits\Banana', '**', true],

          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables', false],
          ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\*', false],
          ['Food\Vegetables\Fruits\Mango', 'Food\**\Banana', false],
          ['Food\Vegetables\Fruits\Mango', '', false],
        ];
    }

    /**
     * @dataProvider patternProvider
     * @param string $fqcn
     * @param string $pattern
     * @param bool $shouldMatch
     */
    public function test_should_match_namespaces_with_wildcards(string $fqcn, string $pattern, bool $shouldMatch): void
    {
        $fqcn = FullyQualifiedClassName::fromString($fqcn);

        $this->assertEquals($shouldMatch, $fqcn->matches($pattern));
    }

    public function test_should_throw_if_invalid_namespace_is_passed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('-Gvnn is not a valid namespace definition');

        FullyQualifiedClassName::fromString('-Gvnn');
    }

    public function test_should_return_class_name(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');

        $this->assertEquals('Banana', $fqcn->className());
    }
}
