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

    public function test_it_should_create_class_dependency(): void
    {
        self::assertEquals(FullyQualifiedClassName::fromString($this->FQCN), $this->classDependency->getFQCN());
        self::assertEquals($this->line, $this->classDependency->getLine());
    }

    public function test_it_should_match(): void
    {
        self::assertTrue($this->classDependency->matches('HappyIsland'));
    }

    public function test_it_should_not_match(): void
    {
        self::assertFalse($this->classDependency->matches('Happy'));
    }

    public function test_it_should_match_one_of(): void
    {
        self::assertTrue($this->classDependency->matchesOneOf('HappyIsland', 'Foo', 'Bar'));
    }

    public function test_it_should_not_match_one_of(): void
    {
        self::assertFalse($this->classDependency->matchesOneOf('Baz', 'Foo', 'Bar'));
    }
}
