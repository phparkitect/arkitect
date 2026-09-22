<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Exceptions\InvalidPatternException;
use PHPUnit\Framework\TestCase;

class FullyQualifiedClassNameTest extends TestCase
{
    public static function patternProvider(): array
    {
        return [
            ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\Fruits\Banana', true],
            ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\*\Banana', true],
            ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables', true],
            ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\\', true],
            ['Food\Vegetables\Fruits\Banana', 'Food\Vegetables\*', true],
            ['Food\Veg', 'Food\Vegetables', false],
            ['Food\Vegetables', 'Food\Veg', false],

            // a wildcard in the middle still matches everything under the namespace it denotes
            ['Food\Vegetables\Roots\Carrot', 'Food\*\Roots', true],
            ['Food\Vegetables\Roots\Orange\Carrot', 'Food\*\Roots', true],
            ['Food\Vegetables\Roots', 'Food\*\Roots', true],
            ['Food\Vegetables\Carrot', '*\Vegetables', true],
            ['Food\Vegetables\Roots\Carrot', '*\Vegetables', true],

            // a pattern matches whole names only, it never reaches into a longer one
            ['Food\VegetablesAndFruits\Carrot', 'Food\Vegetables', false],
            ['Food\Vegetables\RootsAndLeaves\Carrot', 'Food\*\Roots', false],
            ['Food\Vegetables\Roots\Carrot', 'Food\*\Leaves', false],
        ];
    }

    /**
     * @dataProvider provideNamespacesOfBanana
     */
    public function test_a_class_resides_in_every_namespace_above_it(string $namespace): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');

        self::assertTrue($fqcn->matches($namespace));
    }

    public static function provideNamespacesOfBanana(): array
    {
        return [
            'the class itself' => ['Food\Vegetables\Fruits\Banana'],
            'the namespace it sits in' => ['Food\Vegetables\Fruits'],
            'the one above that' => ['Food\Vegetables'],
            'the root one' => ['Food'],
        ];
    }

    public function test_it_matches_the_short_class_name_only_as_a_whole(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');

        self::assertTrue($fqcn->classMatches('Banana'));
        self::assertTrue($fqcn->classMatches('Ban*'));
        self::assertFalse($fqcn->classMatches('Ban'));
        self::assertFalse($fqcn->classMatches('Food\Vegetables\Fruits\Banana'));
    }

    public function test_it_matches_one_of_several_patterns(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');

        self::assertTrue($fqcn->matchesOneOf('Food\Meat', 'Food\*\Fruits'));
        self::assertFalse($fqcn->matchesOneOf('Food\Meat', 'Food\*\Roots'));
        self::assertFalse($fqcn->matchesOneOf());
    }

    /**
     * @dataProvider patternProvider
     */
    public function test_should_match_namespaces_with_wildcards(string $fqcn, string $pattern, bool $shouldMatch): void
    {
        $fqcn = FullyQualifiedClassName::fromString($fqcn);

        self::assertEquals($shouldMatch, $fqcn->matches($pattern), "{$fqcn->toString()} should ".($shouldMatch ? '' : 'not ')."match $pattern");
    }

    public function test_an_empty_namespace_is_rejected_rather_than_matching_nothing(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Mango');

        $this->expectException(InvalidPatternException::class);

        $fqcn->matches('');
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
        self::assertEquals('A', $fqcn->className());
    }

    public function test_should_return_class_name(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');
        self::assertEquals('Banana', $fqcn->className());
    }

    public function test_should_have_root_ns_preserved(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('\Banana');

        self::assertEquals('Banana', $fqcn->className());
        self::assertEquals('', $fqcn->namespace());
    }

    public function test_should_have_ns_normalized(): void
    {
        $fqcn = FullyQualifiedClassName::fromString('Food\Vegetables\Fruits\Banana');

        self::assertEquals('Banana', $fqcn->className());
        self::assertEquals('Food\Vegetables\Fruits', $fqcn->namespace());
        self::assertEquals('Food\Vegetables\Fruits\Banana', $fqcn->toString());
    }
}
