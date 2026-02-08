<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotHaveTrait;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotHaveTraitTest extends TestCase
{
    public function test_it_should_return_no_violation_if_class_does_not_use_trait(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $traitConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_no_violation_if_class_uses_different_trait(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addTrait('AnotherTrait', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $traitConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_if_class_uses_trait(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addTrait('MyTrait', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $traitConstraint->evaluate($classDescription, $violations, $because);

        $violationError = $traitConstraint->describe($classDescription, $because)->toString();

        self::assertNotEquals(0, $violations->count());
        self::assertEquals(
            'should not use the trait MyTrait because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_return_no_violation_if_is_an_interface(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $traitConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_no_violation_if_is_a_trait(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $traitConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_applies_to_should_return_true_for_regular_classes(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        self::assertTrue($traitConstraint->appliesTo($classDescription));
    }

    public function test_applies_to_should_return_false_for_interfaces(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        self::assertFalse($traitConstraint->appliesTo($classDescription));
    }

    public function test_applies_to_should_return_false_for_traits(): void
    {
        $traitConstraint = new NotHaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        self::assertFalse($traitConstraint->appliesTo($classDescription));
    }
}
