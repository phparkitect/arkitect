<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\HaveTrait;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class HaveTraitTest extends TestCase
{
    public function test_it_should_return_true_if_class_uses_trait(): void
    {
        $expression = new HaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->addTrait('MyTrait', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should use the trait MyTrait because we want to add this rule for our software',
            $expression->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_true_if_class_uses_trait_without_because(): void
    {
        $expression = new HaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->addTrait('MyTrait', 1)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should use the trait MyTrait',
            $expression->describe($classDescription, '')->toString()
        );
    }

    public function test_it_should_return_false_if_class_does_not_use_trait(): void
    {
        $expression = new HaveTrait('AnotherTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->addTrait('MyTrait', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_no_violation_if_is_an_interface(): void
    {
        $expression = new HaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_no_violation_if_is_a_trait(): void
    {
        $expression = new HaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_applies_to_should_return_true_for_regular_classes(): void
    {
        $expression = new HaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        self::assertTrue($expression->appliesTo($classDescription));
    }

    public function test_applies_to_should_return_false_for_interfaces(): void
    {
        $expression = new HaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        self::assertFalse($expression->appliesTo($classDescription));
    }

    public function test_applies_to_should_return_false_for_traits(): void
    {
        $expression = new HaveTrait('MyTrait');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        self::assertFalse($expression->appliesTo($classDescription));
    }
}
