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
        $this->assertTrue($pattern->matches('Example'));
        $this->assertFalse($pattern->matches('Something else'));
    }

    public function test_wildcard_is_for_alphanumeric(): void
    {
        $pattern = new PatternString('SoThisIsAnExample');
        $this->assertTrue($pattern->matches('*This*'));
        $this->assertFalse($pattern->matches('This*'));
    }

    public function test_it_can_strictly_match_explicit_prefixes(): void
    {
        $pattern = new PatternString('AlphaBravoCharlie');
        $this->assertTrue($pattern->matches('Alpha*'));
        $this->assertTrue($pattern->matches('AlphaBravo*'));
        $this->assertFalse(
            $pattern->matches('*Alpha*'),
            'There is nothing to match before the first string "Alpha" (e.g. "_AlphaBravoCharlie")'
        );
    }

    public function test_it_can_strictly_match_explicit_postfixes(): void
    {
        $pattern = new PatternString('AlphaBravoCharlie');
        $this->assertTrue($pattern->matches('*Charlie'));
        $this->assertTrue($pattern->matches('*BravoCharlie'));
        $this->assertFalse(
            $pattern->matches('*Charlie*'),
            'There is nothing to match after the last string "Charlie" (e.g. "AlphaBravoCharlie_")'
        );
    }

    public function test_it_can_strictly_match_explicit_infixes(): void
    {
        $pattern = new PatternString('AlphaBravoCharlie');
        $this->assertTrue($pattern->matches('*Bravo*'));
        $this->assertFalse(
            $pattern->matches('*Bravo'),
            'This should resolve to "AlphaBravo" and not match "AlphaBravoCharlie"'
        );
        $this->assertFalse(
            $pattern->matches('Bravo*'),
            'This should resolve to "BravoCharlie" and not match "AlphaBravoCharlie"'
        );
    }
}
