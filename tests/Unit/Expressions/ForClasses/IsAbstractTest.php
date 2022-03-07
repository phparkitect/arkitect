<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\IsAbstract;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsAbstractTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isAbstract = new IsAbstract();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false
        );

        $violationError = $isAbstract->describe($classDescription)->toString();

        $violations = new Violations();
        $isAbstract->evaluate($classDescription, $violations);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('HappyIsland should be abstract', $violationError);
    }

    public function test_it_should_return_true_if_is_abstract(): void
    {
        $isAbstract = new IsAbstract();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            true,
            true
        );

        $violations = new Violations();
        $isAbstract->evaluate($classDescription, $violations);
        self::assertEquals(0, $violations->count());
    }
}
