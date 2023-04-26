<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\IsNotInterface;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsNotInterfaceTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isFinal = new IsNotInterface();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            true,
            false
        );
        $because = 'we want to add this rule for our software';
        $violationError = $isFinal->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $isFinal->evaluate($classDescription, $violations, $because);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('HappyIsland should not be an interface because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_not_interface(): void
    {
        $isFinal = new IsNotInterface();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            false,
            false
        );
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $isFinal->evaluate($classDescription, $violations, $because);
        self::assertEquals(0, $violations->count());
    }
}
