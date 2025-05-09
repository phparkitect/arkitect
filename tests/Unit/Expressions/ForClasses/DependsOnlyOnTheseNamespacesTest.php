<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class DependsOnlyOnTheseNamespacesTest extends TestCase
{
    public function test_it_should_return_true_if_it_has_no_dependencies(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should depend only on classes in one of these namespaces: myNamespace because we want to add this rule for our software',
            $dependOnClasses->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('anotherNamespace\Banana', 1))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
        self::assertEquals(
            'depends on anotherNamespace\Banana, but should depend only on classes in one of these namespaces: myNamespace because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }

    public function test_it_should_return_true_if_depends_on_class_in_root_namespace(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('\anotherNamespace\Banana', 1))
            ->addDependency(new ClassDependency('\DateTime', 10))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();

        $dependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertCount(1, $violations);
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_true_if_depends_on_same_namespace_without_specifying_it(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespaces();

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('HappyIsland\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_false_if_namespace_is_excluded(): void
    {
        $dependOnClasses = new DependsOnlyOnTheseNamespaces(['HappyIsland'], ['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('HappyIsland\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}
