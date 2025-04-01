<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotImplementTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $implementConstraint = new NotImplement('interface');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $implementConstraint = new NotImplement('interface');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addExtends('foo', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $implementConstraint = new NotImplement('interface');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addInterface('interface', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        self::assertNotEquals(0, $violations->count());
        self::assertEquals(
            'should not implement interface because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new NotImplement('interface');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}
