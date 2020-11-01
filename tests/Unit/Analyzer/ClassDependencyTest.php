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

    public function setUp(): void
    {
        $this->FQCN = 'HappyIsland';
        $this->line = 100;

        $this->classDependency = new ClassDependency($this->FQCN, $this->line);
    }

    public function test_it_should_create_class_dependency(): void
    {
        $this->assertEquals(FullyQualifiedClassName::fromString($this->FQCN), $this->classDependency->getFQCN());
        $this->assertEquals($this->line, $this->classDependency->getLine());
    }

    public function test_it_should_match(): void
    {
        $this->assertTrue($this->classDependency->matches('HappyIsland'));
    }

    public function test_it_should_not_match(): void
    {
        $this->assertFalse($this->classDependency->matches('Happy'));
    }
}
