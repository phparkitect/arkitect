<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTestFixtures\ClassWithoutInterface;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTestFixtures\TestClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTestFixtures\TestInterface;
use PHPUnit\Framework\TestCase;

class NotImplementTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $implementConstraint = new NotImplement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutInterface::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $implementConstraint = new NotImplement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutInterface::class)
            ->addExtends(ClassWithoutInterface::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $implementConstraint = new NotImplement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(TestClass::class)
            ->addInterface(TestInterface::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        self::assertNotEquals(0, $violations->count());
        self::assertStringContainsString('should not implement', $violationError);
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new NotImplement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(TestInterface::class)
            ->setInterface(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}

// Test fixtures

namespace Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTestFixtures;

interface TestInterface
{
}

class TestClass implements TestInterface
{
}

class ClassWithoutInterface
{
}
