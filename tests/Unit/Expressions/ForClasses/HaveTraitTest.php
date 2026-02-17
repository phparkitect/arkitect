<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\HaveTrait;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\HaveTraitTest\Fixtures\ClassUsingAnotherTrait;
use Arkitect\Tests\Unit\Expressions\ForClasses\HaveTraitTest\Fixtures\ClassUsingSomeTrait;
use Arkitect\Tests\Unit\Expressions\ForClasses\HaveTraitTest\Fixtures\ChildInheritingSomeTrait;
use Arkitect\Tests\Unit\Expressions\ForClasses\HaveTraitTest\Fixtures\SomeTrait;
use Arkitect\Tests\Unit\Expressions\ForClasses\HaveTraitTest\Fixtures\TraitNotUsingSomeTrait;
use Arkitect\Tests\Unit\Expressions\ForClasses\HaveTraitTest\Fixtures\TraitUsingSomeTrait;
use PHPUnit\Framework\TestCase;

class HaveTraitTest extends TestCase
{
    public function test_it_should_return_no_violation_if_class_uses_trait(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassUsingSomeTrait::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should use the trait '.SomeTrait::class.' because we want to add this rule for our software',
            $expression->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_no_violation_if_class_uses_trait_without_because(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassUsingSomeTrait::class)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should use the trait '.SomeTrait::class,
            $expression->describe($classDescription, '')->toString()
        );
    }

    public function test_it_should_return_violation_if_class_uses_different_trait(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassUsingAnotherTrait::class)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_no_violation_if_is_an_interface(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_if_trait_does_not_use_required_trait(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(TraitNotUsingSomeTrait::class)
            ->setTrait(true)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_no_violation_if_trait_uses_required_trait(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(TraitUsingSomeTrait::class)
            ->setTrait(true)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_applies_to_should_return_true_for_regular_classes(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        self::assertTrue($expression->appliesTo($classDescription));
    }

    public function test_applies_to_should_return_false_for_interfaces(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        self::assertFalse($expression->appliesTo($classDescription));
    }

    public function test_applies_to_should_return_true_for_traits(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        self::assertTrue($expression->appliesTo($classDescription));
    }

    public function test_it_should_detect_trait_inherited_from_parent_via_reflection(): void
    {
        $expression = new HaveTrait(SomeTrait::class);

        // ChildInheritingSomeTrait extends ClassUsingSomeTrait which uses SomeTrait.
        // The ClassDescription only knows the direct parent, not the inherited trait.
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildInheritingSomeTrait::class)
            ->addExtends(ClassUsingSomeTrait::class, 1)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\HaveTraitTest\Fixtures;

trait SomeTrait
{
}

trait AnotherTrait
{
}

trait TraitUsingSomeTrait
{
    use SomeTrait;
}

trait TraitNotUsingSomeTrait
{
}

class ClassUsingSomeTrait
{
    use SomeTrait;
}

class ClassUsingAnotherTrait
{
    use AnotherTrait;
}

class ChildInheritingSomeTrait extends ClassUsingSomeTrait
{
}
