<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\IsA;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\DummyClasses\Banana;
use Arkitect\Tests\Unit\Expressions\ForClasses\DummyClasses\CavendishBanana;
use Arkitect\Tests\Unit\Expressions\ForClasses\DummyClasses\Dog;
use Arkitect\Tests\Unit\Expressions\ForClasses\DummyClasses\DwarfCavendishBanana;
use Arkitect\Tests\Unit\Expressions\ForClasses\DummyClasses\FruitInterface;
use PHPUnit\Framework\TestCase;

final class IsATest extends TestCase
{
    public function test_it_should_have_no_violation_when_it_implements(): void
    {
        $interface = FruitInterface::class;
        $isA = new IsA($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString(CavendishBanana::class),
            [],
            [FullyQualifiedClassName::fromString($interface)],
            null,
            false,
            false,
            false,
            false,
            false
        );

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_no_violation_when_it_extends(): void
    {
        $class = Banana::class;
        $isA = new IsA($class);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString(DwarfCavendishBanana::class),
            [],
            [],
            FullyQualifiedClassName::fromString($class),
            false,
            false,
            false,
            false,
            false
        );

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_violation_if_it_doesnt_extend_nor_implement(): void
    {
        $interface = FruitInterface::class;
        $class = Banana::class;
        $isA = new IsA($class, $interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString(Dog::class),
            [],
            [],
            null,
            false,
            false,
            false,
            false,
            false
        );

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            "should inherit from one of: $class, $interface",
            $isA->describe($classDescription, '')->toString()
        );
    }
}
