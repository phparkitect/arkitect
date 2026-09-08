<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\Boolean;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Boolean\Not;
use Arkitect\Expression\ForClasses\IsInterface;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isNotInterface = new Not(new IsInterface());
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            true,
            false,
            false
        );
        $because = 'we want to add this rule for our software';
        $violationError = $isNotInterface->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $isNotInterface->evaluate($classDescription, $violations, $because);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('must NOT (HappyIsland should be an interface) because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_not_interface(): void
    {
        $isNotInterface = new Not(new IsInterface());
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            false,
            false,
            false
        );
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $isNotInterface->evaluate($classDescription, $violations, $because);
        self::assertEquals(0, $violations->count());
    }
}
