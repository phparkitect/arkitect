<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTestFixtures\AnotherBaseClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTestFixtures\BaseClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTestFixtures\ChildClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTestFixtures\ClassWithoutParent;
use PHPUnit\Framework\TestCase;

class ExtendTest extends TestCase
{
    public function test_it_should_return_no_violation_on_success(): void
    {
        $extend = new Extend(BaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->addExtends(BaseClass::class, 10)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_work_with_wildcards(): void
    {
        $extend = new Extend('My\B14*');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('My\Class')
            ->addExtends('My\B14Class', 10)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_error_when_argument_is_a_regex(): void
    {
        $extend = new Extend('App\Providers\(Auth|Event|Route|Horizon)ServiceProvider');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('My\Class')
            ->addExtends('My\BaseClass', 10)
            ->build();

        $violations = new Violations();
        self::expectExceptionMessage("'App\Providers\(Auth|Event|Route|Horizon)ServiceProvider' is not a valid class or namespace pattern. Regex are not allowed, only * and ? wildcard.");
        $extend->evaluate($classDescription, $violations, 'I said so');
    }

    public function test_it_should_return_violation_error_when_class_not_extend(): void
    {
        $extend = new Extend(BaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutParent::class)
            ->addExtends(AnotherBaseClass::class, 10)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'we want to add this rule for our software');

        self::assertEquals(1, $violations->count());
        self::assertStringContainsString('should extend one of these classes', $violations->get(0)->getError());
    }

    public function test_it_should_return_violation_error_if_extend_is_null(): void
    {
        $extend = new Extend(BaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ClassWithoutParent::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $extend->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertStringContainsString('should extend one of these classes', $violationError);
    }

    public function test_it_should_accept_multiple_extends(): void
    {
        $extend = new Extend(BaseClass::class, AnotherBaseClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->addExtends(BaseClass::class, 10)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }
}

// Test fixtures

namespace Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTestFixtures;

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
