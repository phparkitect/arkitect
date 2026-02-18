<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTest\Fixtures\AnotherInterface;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTest\Fixtures\BaseClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTest\Fixtures\SubClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTest\Fixtures\UnrelatedClass;
use PHPUnit\Framework\TestCase;

class NotImplementTest extends TestCase
{
    public function test_it_should_return_no_violation_when_class_does_not_implement_interface(): void
    {
        $implementConstraint = new NotImplement(AnotherInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(UnrelatedClass::class)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_when_class_directly_implements_interface(): void
    {
        $implementConstraint = new NotImplement(AnotherInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(BaseClass::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);

        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not implement '.AnotherInterface::class.' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $implementConstraint = new NotImplement(AnotherInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setInterface(true)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_detect_inherited_interface_via_reflection(): void
    {
        $implementConstraint = new NotImplement(AnotherInterface::class);

        // SubClass extends BaseClass which implements AnotherInterface.
        // The ClassDescription only knows the direct parent, not the inherited interface.
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(SubClass::class)
            ->build();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, 'because');

        self::assertEquals(1, $violations->count());
    }
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\NotImplementTest\Fixtures;

interface AnotherInterface
{
}

class UnrelatedClass
{
}

class BaseClass implements AnotherInterface
{
}

class SubClass extends BaseClass
{
}
