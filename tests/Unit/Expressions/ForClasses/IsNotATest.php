<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsNotA;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsNotA\Animal\Dog;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsNotA\Fruit\Banana;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsNotA\Fruit\CavendishBanana;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsNotA\Fruit\DwarfCavendishBanana;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsNotA\Fruit\FruitInterface;
use PHPUnit\Framework\TestCase;

final class IsNotATest extends TestCase
{
    public function test_it_should_have_no_violation_when_it_doesnt_extend(): void
    {
        $interface = FruitInterface::class;
        $isNotA = new IsNotA($interface);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(Dog::class)
            ->build();

        $violations = new Violations();
        $isNotA->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_no_violation_when_it_doesnt_implement(): void
    {
        $class = Banana::class;
        $isNotA = new IsNotA($class);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(Dog::class)
            ->build();

        $violations = new Violations();
        $isNotA->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_violation_when_it_implements(): void
    {
        $interface = FruitInterface::class;
        $isNotA = new IsNotA($interface);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(CavendishBanana::class)
            ->addInterface($interface, 10)
            ->build();

        $violations = new Violations();
        $isNotA->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            "CavendishBanana should not be a $interface",
            $isNotA->describe($classDescription, '')->toString()
        );
    }

    public function test_it_should_have_violation_when_it_extends(): void
    {
        $class = Banana::class;
        $isNotA = new IsNotA($class);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(DwarfCavendishBanana::class)
            ->addExtends($class, 10)
            ->build();

        $violations = new Violations();
        $isNotA->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            "DwarfCavendishBanana should not be a $class",
            $isNotA->describe($classDescription, '')->toString()
        );
    }
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\IsNotA\Animal;

final class Dog
{
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\IsNotA\Fruit;

interface FruitInterface
{
}

class Banana implements FruitInterface
{
}

class CavendishBanana extends Banana
{
}

final class DwarfCavendishBanana extends CavendishBanana
{
}
