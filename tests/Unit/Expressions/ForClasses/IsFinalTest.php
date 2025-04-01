<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsNotFinal;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsFinalTest extends TestCase
{
    public function test_it_should_return_error_description(): void
    {
        $isFinal = new IsFinal();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $isFinal->describe($classDescription, $because)->toString();

        self::assertEquals('HappyIsland should be final because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_final(): void
    {
        $isFinal = new IsFinal();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setFinal(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();

        $isFinal->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_abstract_classes_can_not_be_final_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
        $isNotFinal = new IsNotFinal();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setAbstract(true)
            ->build();

        self::assertFalse($isFinal->appliesTo($classDescription));
        self::assertFalse($isNotFinal->appliesTo($classDescription));
    }

    public function test_interfaces_can_not_be_final_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
        $isNotFinal = new IsNotFinal();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        self::assertFalse($isFinal->appliesTo($classDescription));
        self::assertFalse($isNotFinal->appliesTo($classDescription));
    }

    public function test_traits_can_not_be_final_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
        $isNotFinal = new IsNotFinal();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        self::assertFalse($isFinal->appliesTo($classDescription));
        self::assertFalse($isNotFinal->appliesTo($classDescription));
    }

    public function test_enums_can_not_be_final_and_should_be_ignored(): void
    {
        $isFinal = new IsFinal();
        $isNotFinal = new IsNotFinal();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setEnum(true)
            ->build();

        self::assertFalse($isFinal->appliesTo($classDescription));
        self::assertFalse($isNotFinal->appliesTo($classDescription));
    }
}
