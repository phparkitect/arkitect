<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTestFixtures\AnotherBaseClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTestFixtures\BaseClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTestFixtures\ChildClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTestFixtures\ClassWithoutParent;
use PHPUnit\Framework\TestCase;

class NotExtendTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $notExtend = new NotExtend(BaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->addExtends(BaseClass::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertStringContainsString('should not extend', $violationError);
    }

    public function test_it_should_not_return_violation_error_if_extends_another_class(): void
    {
        $notExtend = new NotExtend(BaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutParent::class)
            ->addExtends(AnotherBaseClass::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_error_for_multiple_extends(): void
    {
        $notExtend = new NotExtend(BaseClass::class, AnotherBaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->addExtends(BaseClass::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertStringContainsString('should not extend', $violationError);
    }
}

// Test fixtures

namespace Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTestFixtures;

class BaseClass
{
}

class AnotherBaseClass
{
}

class ChildClass extends BaseClass
{
}

class ClassWithoutParent
{
}
