<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotImplementTest extends TestCase
{
    public function test_it_should_return_no_violation_when_not_implementing(): void
    {
        $implementConstraint = new NotImplement(Serializable::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(SimpleOrder::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_no_violation_when_extending_without_interface(): void
    {
        $implementConstraint = new NotImplement(Serializable::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(SimpleOrder::class)
            ->addExtends(AbstractController::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_when_implementing(): void
    {
        $implementConstraint = new NotImplement(Serializable::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(SerializableOrder::class)
            ->addInterface(Serializable::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        self::assertNotEquals(0, $violations->count());
        self::assertEquals(
            'should not implement ' . Serializable::class . ' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new NotImplement(Serializable::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(SimpleOrder::class)
            ->setInterface(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}

// Fixtures

interface Serializable
{
}

class SerializableOrder implements Serializable
{
}

class SimpleOrder
{
}
