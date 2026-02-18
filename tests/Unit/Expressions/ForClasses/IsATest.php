<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsA;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsOneOfTest\Animal\Dog;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsOneOfTest\Fruit\Banana;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsOneOfTest\Fruit\CavendishBanana;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsOneOfTest\Fruit\DwarfCavendishBanana;
use Arkitect\Tests\Unit\Expressions\ForClasses\IsOneOfTest\Fruit\FruitInterface;
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
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_violation_if_it_doesnt_extend(): void
    {
        $interface = FruitInterface::class;
        $isA = new IsA($interface);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(Dog::class)
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            "should inherit from: $interface",
            $isA->describe($classDescription, '')->toString()
        );
    }

    public function test_it_should_have_violation_if_it_doesnt_implement(): void
    {
        $class = Banana::class;
        $isA = new IsA($class);
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(Dog::class)
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            "should inherit from: $class",
            $isA->describe($classDescription, '')->toString()
        );
    }
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\IsOneOfTest\Animal;

final class Dog
{
}

namespace Arkitect\Tests\Unit\Expressions\ForClasses\IsOneOfTest\Fruit;

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
