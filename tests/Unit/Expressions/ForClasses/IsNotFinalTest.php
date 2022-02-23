<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\IsNotFinal;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsNotFinalTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isFinal = new IsNotFinal();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            true
        );

        $violationError = $isFinal->describe($classDescription)->toString();

        $violations = new Violations();
        $isFinal->evaluate($classDescription, $violations);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('HappyIsland should not be final', $violationError);
    }

    public function test_it_should_return_true_if_is_final(): void
    {
        $isFinal = new IsNotFinal();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false
        );

        $violations = new Violations();
        $isFinal->evaluate($classDescription, $violations);
        self::assertEquals(0, $violations->count());
    }
}
