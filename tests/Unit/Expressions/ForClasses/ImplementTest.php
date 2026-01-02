<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTestFixtures\AnotherInterface;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTestFixtures\ClassWithoutInterface;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTestFixtures\TestClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTestFixtures\TestInterface;
use PHPUnit\Framework\TestCase;

class ImplementTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $implementConstraint = new Implement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutInterface::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
        self::assertStringContainsString('should implement', $violationError);
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $implementConstraint = new Implement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutInterface::class)
            ->addInterface(AnotherInterface::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $implementConstraint = new Implement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(TestClass::class)
            ->addInterface(TestInterface::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_check_the_complete_fqcn(): void
    {
        $implementConstraint = new Implement(TestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutInterface::class)
            ->addInterface(AnotherInterface::class, 1)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new Implement(TestInterface::class);

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

namespace Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTestFixtures;

interface TestInterface
{
}

interface AnotherInterface
{
}

class TestClass implements TestInterface
{
}

class ClassWithoutInterface
{
}
