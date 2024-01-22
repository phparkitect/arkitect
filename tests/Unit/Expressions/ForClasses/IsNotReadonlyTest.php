<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\IsNotReadonly;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsNotReadonlyTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isNotReadonly = new IsNotReadonly();

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            true,
            false,
            false,
            false,
            false
        );
        $because = 'we want to add this rule for our software';
        $violationError = $isNotReadonly->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $isNotReadonly->evaluate($classDescription, $violations, $because);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('HappyIsland should not be readonly because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_not_readonly(): void
    {
        $isNotReadonly = new IsNotReadonly();

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            false,
            false,
            false,
            false
        );
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $isNotReadonly->evaluate($classDescription, $violations, $because);
        self::assertEquals(0, $violations->count());
    }
}
