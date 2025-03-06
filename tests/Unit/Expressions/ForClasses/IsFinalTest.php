<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsFinalTest extends TestCase
{
    public function test_it_should_return_error_description(): void
    {
        $isFinal = new IsFinal();
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
        $violationError = $isFinal->describe($classDescription, $because)->toString();

        $this->assertEquals('HappyIsland should be final because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_final(): void
    {
        $isFinal = new IsFinal();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            true,
            false,
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

    public function test_final_classes_can_not_be_abstract_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            true,
            false,
            false,
            false
        );

        self::assertFalse($isFinal->appliesTo($classDescription));
    }

    public function test_interfaces_can_not_be_final_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            false,
            true,
            false,
            false
        );

        self::assertFalse($isFinal->appliesTo($classDescription));
    }

    public function test_traits_can_not_be_final_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            false,
            false,
            true,
            false
        );

        self::assertFalse($isFinal->appliesTo($classDescription));
    }

    public function test_enums_can_not_be_final_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
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
            true
        );

        self::assertFalse($isFinal->appliesTo($classDescription));
    }
}
