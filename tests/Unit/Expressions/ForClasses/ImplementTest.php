<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

interface ImplementTestInterface
{
}

interface ImplementTestAnotherInterface
{
}

class ImplementTestClassWithInterface implements ImplementTestInterface
{
}

class ImplementTestClassWithoutInterface
{
}

class ImplementTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $implementConstraint = new Implement(ImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ImplementTestClassWithoutInterface::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
        self::assertEquals(
            'should implement ' . ImplementTestInterface::class . ' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $implementConstraint = new Implement(ImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ImplementTestClassWithoutInterface::class)
            ->addInterface(ImplementTestAnotherInterface::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $implementConstraint = new Implement(ImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ImplementTestClassWithInterface::class)
            ->addInterface(ImplementTestInterface::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_check_the_complete_fqcn(): void
    {
        $implementConstraint = new Implement(ImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ImplementTestClassWithoutInterface::class)
            ->addInterface(ImplementTestAnotherInterface::class, 1)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new Implement(ImplementTestInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ImplementTestClassWithoutInterface::class)
            ->setInterface(true)
            ->build();

        $because = 'we want to add this rule for our software';

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}
