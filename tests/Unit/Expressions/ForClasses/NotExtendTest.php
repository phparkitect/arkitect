<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTest\Fixtures\ChildClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTest\Fixtures\GrandParentClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTest\Fixtures\MiddleClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTest\Fixtures\StandaloneClass;
use PHPUnit\Framework\TestCase;

class NotExtendTest extends TestCase
{
    public function test_it_should_return_violation_when_class_directly_extends(): void
    {
        $notExtend = new NotExtend(MiddleClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not extend one of these classes: '.MiddleClass::class.' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_not_return_violation_when_class_has_no_parent(): void
    {
        $notExtend = new NotExtend(GrandParentClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(StandaloneClass::class)
            ->build();

        $violations = new Violations();
        $notExtend->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_for_one_matching_class_among_multiple(): void
    {
        $notExtend = new NotExtend(StandaloneClass::class, MiddleClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not extend one of these classes: '.StandaloneClass::class.', '.MiddleClass::class.' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_detect_grandparent_as_violation_via_reflection(): void
    {
        $notExtend = new NotExtend(GrandParentClass::class);

        // ChildClass extends MiddleClass extends GrandParentClass.
        // The ClassDescription only knows the direct parent (MiddleClass).
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ChildClass::class)
            ->addExtends(MiddleClass::class, 1)
            ->build();

        $violations = new Violations();
        $notExtend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(1, $violations->count());
    }
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\NotExtendTest\Fixtures;

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
