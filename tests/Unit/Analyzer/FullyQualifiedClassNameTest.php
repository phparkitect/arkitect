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
            ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\\', true],
            ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\*', true],
            ['Food\Vegetables\Fruits\Mango', '', false],
            ['Food\Veg', 'Food\Vegetables', false],
            ['Food\Vegetables', 'Food\Veg', false],
        ];
    }

    /**
     * @dataProvider patternProvider
     */
    public function test_should_match_namespaces_with_wildcards(string $fqcn, string $pattern, bool $shouldMatch): void
    {
        $fqcn = FullyQualifiedClassName::fromString($fqcn);

        $this->assertEquals($shouldMatch, $fqcn->matches($pattern), "{$fqcn->toString()} should ".($shouldMatch ? '' : 'not ')."match $pattern");
    }

    public function test_should_throw_if_invalid_namespace_is_passed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('-Gvnn is not a valid namespace definition');

        FullyQualifiedClassName::fromString('-Gvnn');
    }

    public function test_single_letter_class_is_valid(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('A');
        $this->assertEquals('A', $fqcn->className());
    }

    public function test_should_return_class_name(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');
        $this->assertEquals('Banana', $fqcn->className());
    }

    public function test_should_have_root_ns_preserved(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('\Banana');

        $this->assertEquals('Banana', $fqcn->className());
        $this->assertEquals('', $fqcn->namespace());
    }

    public function test_should_have_ns_normalized(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');

        $this->assertEquals('Banana', $fqcn->className());
        $this->assertEquals('Food\Vegetables\Fruits', $fqcn->namespace());
        $this->assertEquals('Food\Vegetables\Fruits\Banana', $fqcn->toString());
    }
}
