<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\Pattern;
use Arkitect\Exceptions\InvalidPatternException;
use PHPUnit\Framework\TestCase;

class PatternTest extends TestCase
{
    public function test_it_works_for_simple_strings(): void
    {
        self::assertTrue(Pattern::fromString('Example')->matches('Example'));
        self::assertFalse(Pattern::fromString('Example')->matches('Something else'));
    }

    /**
     * @dataProvider providePatterns
     */
    public function test_wildcard_is_for_alphanumeric(string $string, string $pattern, bool $expectedResult): void
    {
        self::assertEquals($expectedResult, Pattern::fromString($pattern)->matches($string));
    }

    public static function providePatterns(): array
    {
        return [
            ['SoThisIsAnExample', '*This*', true],
            ['SoThisIsAnExample', 'This*', false],
            ['SoThisIsAnExample', '*This', false],
            ['SoThisIsAnExample', 'SoThisIsAnExample', true],
            ['SoThisIsAnExample', 'So????????Example', true],
            ['SoThisIsAnExample', '*SoThisIsAnExample', true],
            ['SoThisIsAnExample', 'SoThisIsAnExample*', true],
            ['SoThisIsAnExample', 'So*Example', true],
            ['SoThisIsAnExample', '*ThisIsAnExample', true],
            ['SoThisIsAnExample', 'SoThisIsAn*', true],
            ['SoThisIsAnExample', '*Example', true],
            ['Food\Vegetables\Roots\Carrot', 'Food\*\Roots', false],
            ['Food\Vegetables\Roots\Orange\Carrot', 'Food\*\Roots', false],
            ['Food\Vegetables\Carrot', '*\Vegetables', false],
            ['Food\Vegetables\Roots\Carrot', '*\Vegetables', false],
            ['Food\Vegetables\Roots\Orange\Carrot', '*\Vegetables', false],
        ];
    }

    /**
     * @dataProvider providePatternsNamingNothing
     */
    public function test_a_pattern_that_names_nothing_is_rejected(string $pattern): void
    {
        $this->expectException(InvalidPatternException::class);
        $this->expectExceptionMessage('names no class and no namespace');

        Pattern::fromString($pattern);
    }

    public static function providePatternsNamingNothing(): array
    {
        return [
            'an empty pattern' => [''],
            'a lone separator' => ['\\'],
            'nothing but separators' => ['\\\\\\'],
        ];
    }

    public function test_a_trailing_separator_denotes_the_same_namespace(): void
    {
        $pattern = Pattern::fromString('Food\Vegetables\\');

        self::assertEquals('Food\Vegetables', $pattern->toString());
        self::assertTrue($pattern->matches('Food\Vegetables'));
    }

    /**
     * @dataProvider provideInvalidPatterns
     */
    public function test_it_rejects_anything_that_is_not_a_wildcard(string $pattern): void
    {
        $this->expectException(InvalidPatternException::class);
        $this->expectExceptionMessage("'$pattern' is not a valid class or namespace pattern.");

        Pattern::fromString($pattern);
    }

    public static function provideInvalidPatterns(): array
    {
        return [
            'a regex any-char' => ['Food.Vegetables'],
            'a regex character class' => ['Food\[AB]Vegetables'],
            'a regex quantifier' => ['Food\Vegetables+'],
            'a regex anchor' => ['^Food\Vegetables$'],
        ];
    }
}
