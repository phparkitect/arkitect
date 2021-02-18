<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespace;
use PHPUnit\Framework\TestCase;

class DependsOnlyOnTheseNamespaceTest extends TestCase
{
    public function test_it_should_return_true_if_it_has_no_dependencies(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespace('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass', 'full/path')->get();

        self::assertTrue($dependOnClasses->evaluate($classDescription));
        self::assertEquals('should depends only on classes in one of these namespaces: myNamespace', $dependOnClasses->describe($classDescription)->toString());
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespace('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass', 'full/path')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('anotherNamespace\Banana', 1))
            ->get();

        self::assertFalse($dependOnClasses->evaluate($classDescription));
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespace('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass', 'full/path')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->get();

        self::assertTrue($dependOnClasses->evaluate($classDescription));
    }
}
