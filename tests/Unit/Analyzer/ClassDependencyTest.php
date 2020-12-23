<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\FullyQualifiedClassName;
use PHPUnit\Framework\TestCase;

class ClassDependencyTest extends TestCase
{
    private $FQCN;
    private $line;
    private $classDependency;

    protected function setUp(): void
    {
        $this->FQCN = 'HappyIsland';
        $this->line = 100;

        $this->classDependency = new ClassDependency($this->FQCN, $this->line);
    }

    public function testItShouldCreateClassDependency(): void
    {
        $this->assertEquals(FullyQualifiedClassName::fromString($this->FQCN), $this->classDependency->getFQCN());
        $this->assertEquals($this->line, $this->classDependency->getLine());
    }

    public function testItShouldMatch(): void
    {
        $this->assertTrue($this->classDependency->matches('HappyIsland'));
    }

    public function testItShouldNotMatch(): void
    {
        $this->assertTrue($this->classDependency->matches('Happy'));
    }
}
