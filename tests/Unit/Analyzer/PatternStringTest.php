<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\PatternString;
use PHPUnit\Framework\TestCase;

class PatternStringTest extends TestCase
{
    public function testItWorksForSimpleStrings(): void
    {
        $pattern = new PatternString('Example');
        $this->assertTrue($pattern->matches('Example'));
        $this->assertFalse($pattern->matches('Something else'));
    }

    public function testWildcardIsForAlphanumeric(): void
    {
        $pattern = new PatternString('SoThisIsAnExample');
        $this->assertTrue($pattern->matches('*This*'));
        $this->assertFalse($pattern->matches('This*'));
    }

    public function testExplode(): void
    {
        $pattern = new PatternString('So This Is An Example');
        $this->assertEquals(['So', 'This', 'Is', 'An', 'Example'], $pattern->explode(' '));
    }
}
