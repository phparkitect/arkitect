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

    public function test_ends_with(): void
    {
        $pattern = new PatternString('SoThisIsAnExample');
        $this->assertTrue($pattern->matches('*Example'));
        $this->assertFalse($pattern->matches('*This'));
    }
}
