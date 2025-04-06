<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\PatternString;
use PHPUnit\Framework\TestCase;

class PatternStringTest extends TestCase
{
    public function test_it_works_for_simple_strings(): void
    {
        $pattern = new PatternString('Example');
        self::assertTrue($pattern->matches('Example'));
        self::assertFalse($pattern->matches('Something else'));
    }

    /**
     * @dataProvider providePatterns
     */
    public function test_wildcard_is_for_alphanumeric(string $string, string $pattern, bool $expectedResult): void
    {
        self::assertEquals($expectedResult, (new PatternString($string))->matches($pattern));
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
}
