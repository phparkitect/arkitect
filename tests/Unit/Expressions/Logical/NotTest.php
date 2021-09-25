<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\Logical;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\Logical\Not;
use Arkitect\Expression\NegativeDescription;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotTest extends TestCase
{
    /**
     * @var ClassDescription
     */
    private $classDescription;
    /**
     * @var Not
     */
    private $not;

    protected function setUp(): void
    {
        $name = 'Domain';
        $expression = new HaveNameMatching($name);
        $this->not = new Not($expression);

        $this->classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null
        );
    }

    public function test_describe_not_expression(): void
    {
        $this->markTestSkipped('Not is deprecated');
        $expected = new NegativeDescription(
            new PositiveDescription('should [have|not have] a name that matches Domain')
        );

        $this->assertEquals($expected, $this->not->describe($this->classDescription));
    }

    public function test_evaluate_not_expression(): void
    {
        $this->expectNotToPerformAssertions();
        $this->not->evaluate($this->classDescription, new Violations());
    }
}
