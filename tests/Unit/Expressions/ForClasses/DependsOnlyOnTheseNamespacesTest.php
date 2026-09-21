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

    public function test_a_dependency_in_a_sub_namespace_of_a_wildcard_namespace_is_allowed(): void
    {
        $dependsOnlyOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['App\*\Infrastructure']);

        $classDescription = ClassDescription::getBuilder('App\Billing\Domain\Invoice', 'src/Invoice.php')
            ->addDependency(new ClassDependency('App\Billing\Infrastructure\DoctrineInvoiceRepository', 10))
            ->build();

        $violations = new Violations();
        $dependsOnlyOnTheseNamespaces->evaluate($classDescription, $violations, 'the domain may only use infrastructure');

        self::assertEquals(0, $violations->count());
    }

    /**
     * A class may always use what sits next to it, so its own namespace does not
     * have to be whitelisted. "Its own" means exactly that namespace: a parent,
     * a child and a sibling are all somebody else's, and have to be allowed
     * explicitly like any other dependency.
     *
     * @dataProvider provideDependenciesOfAClassInFooBar
     */
    public function test_only_the_namespace_the_class_sits_in_needs_no_whitelisting(
        string $dependency,
        bool $isAllowed,
    ): void {
        $dependsOnlyOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['FizzBuzz']);

        $classDescription = ClassDescription::getBuilder('Foo\Bar\FooBar', 'src/FooBar.php')
            ->addDependency(new ClassDependency($dependency, 10))
            ->build();

        $violations = new Violations();
        $dependsOnlyOnTheseNamespaces->evaluate($classDescription, $violations, 'we want to control our dependencies');

        self::assertEquals($isAllowed ? 0 : 1, $violations->count());
    }

    public static function provideDependenciesOfAClassInFooBar(): array
    {
        return [
            'a class in the same namespace' => ['Foo\Bar\Collaborator', true],
            'a class in the whitelisted namespace' => ['FizzBuzz\Collaborator', true],
            'a class in a parent namespace' => ['Foo\Collaborator', false],
            'a class in a child namespace' => ['Foo\Bar\Baz\Collaborator', false],
            'a class in a sibling namespace' => ['Foo\Qux\Collaborator', false],
            'a class in an unrelated namespace' => ['Other\Collaborator', false],
            'a class in the global namespace' => ['Collaborator', false],
        ];
    }

    public function test_a_class_in_the_global_namespace_does_not_own_it(): void
    {
        $dependsOnlyOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['FizzBuzz']);

        $classDescription = ClassDescription::getBuilder('FooBar', 'src/FooBar.php')
            ->addDependency(new ClassDependency('Collaborator', 10))
            ->build();

        $violations = new Violations();
        $dependsOnlyOnTheseNamespaces->evaluate($classDescription, $violations, 'we want to control our dependencies');

        self::assertEquals(1, $violations->count());
    }
}
