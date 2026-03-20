<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

interface NotImplementTestInterface
{
}

class NotImplementTestClassWithInterface implements NotImplementTestInterface
{
}

class NotImplementTestClassWithoutInterface
{
}

class NotImplementTest extends TestCase
{
    public function test_it_should_return_no_violation_when_not_implementing(): void
    {
        $implementConstraint = new NotImplement(NotImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(NotImplementTestClassWithoutInterface::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_no_violation_when_extending_without_interface(): void
    {
        $implementConstraint = new NotImplement(NotImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(NotImplementTestClassWithoutInterface::class)
            ->addExtends(NotExtendTestBaseClass::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_when_implementing(): void
    {
        $implementConstraint = new NotImplement(NotImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(NotImplementTestClassWithInterface::class)
            ->addInterface(NotImplementTestInterface::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        self::assertNotEquals(0, $violations->count());
        self::assertEquals(
            'should not implement ' . NotImplementTestInterface::class . ' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new NotImplement(NotImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(NotImplementTestClassWithoutInterface::class)
            ->setInterface(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}
