<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTest\Fixtures\AnotherInterface;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTest\Fixtures\ConcreteClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTest\Fixtures\DerivedClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTest\Fixtures\SomeInterface;
use Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTest\Fixtures\UnrelatedClass;
use PHPUnit\Framework\TestCase;

class ImplementTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $implementConstraint = new Implement(SomeInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(UnrelatedClass::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_violation_when_class_implements_different_interface(): void
    {
        $implementConstraint = new Implement(AnotherInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ConcreteClass::class)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_no_violation_when_class_directly_implements_interface(): void
    {
        $implementConstraint = new Implement(SomeInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ConcreteClass::class)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_describe_correctly(): void
    {
        $implementConstraint = new Implement(SomeInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(UnrelatedClass::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        self::assertEquals('should implement '.SomeInterface::class.' because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new Implement(SomeInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_detect_interface_inherited_from_parent_via_reflection(): void
    {
        $implementConstraint = new Implement(SomeInterface::class);

        // DerivedClass extends ConcreteClass which implements SomeInterface.
        // The ClassDescription only knows the direct parent, not the inherited interface.
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(DerivedClass::class)
            ->addExtends(ConcreteClass::class, 1)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\ImplementTest\Fixtures;

interface SomeInterface
{
}

interface AnotherInterface
{
}

class ConcreteClass implements SomeInterface
{
}

class UnrelatedClass
{
}

class DerivedClass extends ConcreteClass
{
}
