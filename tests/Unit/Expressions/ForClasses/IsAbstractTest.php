<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsAbstract;
use Arkitect\Expression\ForClasses\IsNotAbstract;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsAbstractTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isAbstract = new IsAbstract();

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('HappyIsland')
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $isAbstract->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $isAbstract->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
        self::assertEquals('HappyIsland should be abstract because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_abstract(): void
    {
        $isAbstract = new IsAbstract();

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('HappyIsland')
            ->setFinal(true)
            ->setReadonly(true)
            ->setAbstract(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $isAbstract->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_interfaces_can_not_be_abstract_and_should_be_ignored(): void
    {
        $isAbstract = new IsAbstract();
        $isNotAbstract = new IsNotAbstract();

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        self::assertFalse($isAbstract->appliesTo($classDescription));
        self::assertFalse($isNotAbstract->appliesTo($classDescription));
    }

    public function test_traits_can_not_be_abstract_and_should_be_ignored(): void
    {
        $isAbstract = new IsAbstract();
        $isNotAbstract = new IsNotAbstract();

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        self::assertFalse($isAbstract->appliesTo($classDescription));
        self::assertFalse($isNotAbstract->appliesTo($classDescription));
    }

    public function test_enums_can_not_be_abstract_and_should_be_ignored(): void
    {
        $isAbstract = new IsAbstract();
        $isNotAbstract = new IsNotAbstract();

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('HappyIsland')
            ->setEnum(true)
            ->build();

        self::assertFalse($isAbstract->appliesTo($classDescription));
        self::assertFalse($isNotAbstract->appliesTo($classDescription));
    }

    public function test_final_classes_can_not_be_abstract_and_should_be_ignored(): void
    {
        $isAbstract = new IsAbstract();
        $isNotAbstract = new IsNotAbstract();

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('HappyIsland')
            ->setFinal(true)
            ->build();

        self::assertFalse($isAbstract->appliesTo($classDescription));
        self::assertFalse($isNotAbstract->appliesTo($classDescription));
    }
}
