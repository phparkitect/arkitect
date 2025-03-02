<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\IsReadonly;
use Arkitect\Rules\Specs;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsReadonlyTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isReadonly = new IsReadonly();

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
        $violationError = $isReadonly->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $isReadonly->evaluate($classDescription, $violations, $because);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('HappyIsland should be readonly because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_readonly(): void
    {
        $isReadonly = new IsReadonly();

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
        $violations = new Violations();
        $isReadonly->evaluate($classDescription, $violations, $because);
        self::assertEquals(0, $violations->count());
    }

    public function test_interfaces_can_not_be_readonly_and_should_be_ignored(): void
    {
        $specStore = new Specs();
        $specStore->add(new IsReadonly());

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

        $because = 'we want to add this rule for our software';

        $this->assertFalse($specStore->allSpecsAreMatchedBy($classDescription, $because));
    }

    public function test_traits_can_not_be_readonly_and_should_be_ignored(): void
    {
        $specStore = new Specs();
        $specStore->add(new IsReadonly());

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
        $because = 'we want to add this rule for our software';

        $this->assertFalse($specStore->allSpecsAreMatchedBy($classDescription, $because));
    }

    public function test_enums_can_not_be_readonly_and_should_be_ignored(): void
    {
        $specStore = new Specs();
        $specStore->add(new IsReadonly());

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
        $because = 'we want to add this rule for our software';

        $this->assertFalse($specStore->allSpecsAreMatchedBy($classDescription, $because));
    }
}
