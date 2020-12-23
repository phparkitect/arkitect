<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespace;
use PHPUnit\Framework\TestCase;

class DependsOnlyOnTheseNamespaceTest extends TestCase
{
    public function testItShouldReturnTrueIfItHasNoDependencies(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespace('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass', 'full/path')->get();

        self::assertTrue($dependOnClasses->evaluate($classDescription));
        self::assertEquals('HappyIsland\Myclass depends only on classes in one of these namespaces: myNamespace', $dependOnClasses->describe($classDescription)->toString());
    }

    public function testItShouldReturnTrueIfNotDependsOnNamespace(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespace('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass', 'full/path')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('anotherNamespace\Banana', 1))
            ->get();

        self::assertFalse($dependOnClasses->evaluate($classDescription));
    }

    public function testItShouldReturnFalseIfDependsOnNamespace(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespace('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass', 'full/path')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->get();

        self::assertTrue($dependOnClasses->evaluate($classDescription));
    }
}
