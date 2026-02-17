<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTest\Fixtures\ChildClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTest\Fixtures\GrandParentClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTest\Fixtures\MiddleClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTest\Fixtures\StandaloneClass;
use PHPUnit\Framework\TestCase;

class ExtendTest extends TestCase
{
    public function test_it_should_return_no_violation_when_class_directly_extends(): void
    {
        $extend = new Extend(MiddleClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_work_with_wildcards(): void
    {
        $extend = new Extend('*MiddleClass');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
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
            ->setClassName(ChildClass::class)
            ->build();

        $violations = new Violations();
        self::expectExceptionMessage("'App\Providers\(Auth|Event|Route|Horizon)ServiceProvider' is not a valid class or namespace pattern. Regex are not allowed, only * and ? wildcard.");
        $extend->evaluate($classDescription, $violations, 'I said so');
    }

    public function test_it_should_return_violation_error_when_class_not_extend(): void
    {
        $extend = new Extend(GrandParentClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(StandaloneClass::class)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'we want to add this rule for our software');

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should extend one of these classes: '.GrandParentClass::class.' because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }

    public function test_it_should_return_violation_error_if_has_no_parent(): void
    {
        $extend = new Extend(GrandParentClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(StandaloneClass::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $extend->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should extend one of these classes: '.GrandParentClass::class.' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_accept_multiple_class_names(): void
    {
        $extend = new Extend(StandaloneClass::class, MiddleClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_detect_grandparent_via_reflection(): void
    {
        $extend = new Extend(GrandParentClass::class);

        // ChildClass extends MiddleClass extends GrandParentClass.
        // The ClassDescription only knows the direct parent (MiddleClass).
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->addExtends(MiddleClass::class, 10)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\ExtendTest\Fixtures;

abstract class GrandParentClass
{
}

abstract class MiddleClass extends GrandParentClass
{
}

class ChildClass extends MiddleClass
{
}

class StandaloneClass
{
}
