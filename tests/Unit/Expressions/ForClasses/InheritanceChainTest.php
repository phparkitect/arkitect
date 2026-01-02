<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\InheritanceChainTestFixtures\BaseInterface;
use Arkitect\Tests\Unit\Expressions\ForClasses\InheritanceChainTestFixtures\ChildClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\InheritanceChainTestFixtures\FinalClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\InheritanceChainTestFixtures\GrandParentClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\InheritanceChainTestFixtures\MiddleClass;
use Arkitect\Tests\Unit\Expressions\ForClasses\InheritanceChainTestFixtures\ParentClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for issue #169: Inheritance Issues with Implement and Extend
 * These tests verify that the rules check the entire inheritance chain,
 * not just direct parent classes or interfaces.
 */
class InheritanceChainTest extends TestCase
{
    public function test_extend_should_recognize_grandparent_class(): void
    {
        $extend = new Extend(GrandParentClass::class);

        // ChildClass extends ParentClass extends GrandParentClass
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Test.php')
            ->setClassName(ChildClass::class)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'testing inheritance chain');

        self::assertEquals(
            0,
            $violations->count(),
            'Should recognize GrandParentClass in the inheritance chain of ChildClass'
        );
    }

    public function test_implement_should_recognize_interface_from_parent_class(): void
    {
        $implement = new Implement(BaseInterface::class);

        // FinalClass extends MiddleClass implements BaseInterface
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Test.php')
            ->setClassName(FinalClass::class)
            ->build();

        $violations = new Violations();
        $implement->evaluate($classDescription, $violations, 'testing inheritance chain');

        self::assertEquals(
            0,
            $violations->count(),
            'Should recognize BaseInterface implemented by parent class MiddleClass'
        );
    }

    public function test_extend_should_work_with_direct_parent(): void
    {
        $extend = new Extend(ParentClass::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Test.php')
            ->setClassName(ChildClass::class)
            ->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'testing direct parent');

        self::assertEquals(
            0,
            $violations->count(),
            'Should still work with direct parent class'
        );
    }

    public function test_implement_should_work_with_direct_interface(): void
    {
        $implement = new Implement(BaseInterface::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Test.php')
            ->setClassName(MiddleClass::class)
            ->build();

        $violations = new Violations();
        $implement->evaluate($classDescription, $violations, 'testing direct interface');

        self::assertEquals(
            0,
            $violations->count(),
            'Should still work with directly implemented interface'
        );
    }
}

// Test fixtures

namespace Arkitect\Tests\Unit\Expressions\ForClasses\InheritanceChainTestFixtures;

// Interface hierarchy test
interface BaseInterface
{
}

class MiddleClass implements BaseInterface
{
}

class FinalClass extends MiddleClass
{
}

// Class hierarchy test
class GrandParentClass
{
}

class ParentClass extends GrandParentClass
{
}

class ChildClass extends ParentClass
{
}
