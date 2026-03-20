<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotExtendTestBaseClass
{
}

class NotExtendTestAnotherClass
{
}

class NotExtendTestChildClass extends NotExtendTestBaseClass
{
}

class NotExtendTestFirstBase
{
}

class NotExtendTestSecondBase
{
}

class NotExtendTestSecondChild extends NotExtendTestSecondBase
{
}

class NotExtendTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $notExtend = new NotExtend(NotExtendTestBaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(NotExtendTestChildClass::class)
            ->addExtends(NotExtendTestBaseClass::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not extend one of these classes: ' . NotExtendTestBaseClass::class . ' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_not_return_violation_error_if_extends_another_class(): void
    {
        $notExtend = new NotExtend(NotExtendTestBaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(NotExtendTestChildClass::class)
            ->addExtends(NotExtendTestAnotherClass::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_error_for_multiple_extends(): void
    {
        $notExtend = new NotExtend(NotExtendTestFirstBase::class, NotExtendTestSecondBase::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(NotExtendTestSecondChild::class)
            ->addExtends(NotExtendTestSecondBase::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not extend one of these classes: ' . NotExtendTestFirstBase::class . ', ' . NotExtendTestSecondBase::class . ' because we want to add this rule for our software',
            $violationError
        );
    }
}
