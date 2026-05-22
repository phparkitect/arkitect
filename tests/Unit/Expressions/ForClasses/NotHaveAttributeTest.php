<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotHaveAttribute;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotHaveAttributeTest extends TestCase
{
    public function test_it_should_return_no_violation_if_class_does_not_have_attribute(): void
    {
        $expression = new NotHaveAttribute('myAttribute');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_no_violation_if_class_has_different_attribute(): void
    {
        $expression = new NotHaveAttribute('myAttribute');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->addAttribute('anotherAttribute', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_if_class_has_attribute(): void
    {
        $expression = new NotHaveAttribute('myAttribute');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->addAttribute('myAttribute', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not have the attribute myAttribute because we want to add this rule for our software',
            $expression->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_correct_description_without_because(): void
    {
        $expression = new NotHaveAttribute('myAttribute');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->build();

        self::assertEquals(
            'should not have the attribute myAttribute',
            $expression->describe($classDescription, '')->toString()
        );
    }
}
