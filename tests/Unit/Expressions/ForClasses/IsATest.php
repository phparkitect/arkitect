<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
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
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(CavendishBanana::class)
            ->addInterface($interface, 10)
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_no_violation_when_it_extends(): void
    {
        $class = Banana::class;
        $isA = new IsA($class);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(DwarfCavendishBanana::class)
            ->addExtends($class, 10)
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_violation_if_it_doesnt_extend_nor_implement(): void
    {
        $interface = FruitInterface::class;
        $class = Banana::class;
        $isA = new IsA($class, $interface);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(Dog::class)
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            "should inherit from one of: $class, $interface",
            $isA->describe($classDescription, '')->toString()
        );
    }
}
