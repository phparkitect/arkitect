<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotDependsOnTheseNamespacesTest extends TestCase
{
    public function test_it_should_return_true_if_it_has_no_dependencies(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('anotherNamespace\Banana', 1))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'depends on myNamespace\Banana, but should not depend on these namespaces: myNamespace because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }

    public function test_it_should_return_true_if_depends_on_class_in_root_namespace(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('\anotherNamespace\Banana', 1))
            ->addDependency(new ClassDependency('\DateTime', 10))
            ->build();

        $violations = new Violations();
        $because = 'we want to add this rule for our software';
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertCount(1, $violations);
        self::assertEquals(
            'depends on myNamespace\Banana, but should not depend on these namespaces: myNamespace because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces(['myNamespace']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(2, $violations->count());
        self::assertEquals(
            'depends on myNamespace\Banana, but should not depend on these namespaces: myNamespace because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }

    public function test_it_should_ignore_excluded_namespaces(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces(['myNamespace'], ['myNamespace\Mango']);

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass', 'src/Foo.php')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }

    public function test_a_namespace_with_a_wildcard_matches_a_dependency_in_a_sub_namespace(): void
    {
        $notDependsOnTheseNamespaces = new NotDependsOnTheseNamespaces(['App\*\Infrastructure']);

        $classDescription = ClassDescription::getBuilder('App\Billing\Domain\Invoice', 'src/Invoice.php')
            ->addDependency(new ClassDependency('App\Billing\Infrastructure\DoctrineInvoiceRepository', 10))
            ->build();

        $violations = new Violations();
        $notDependsOnTheseNamespaces->evaluate($classDescription, $violations, 'the domain must not know about infrastructure');

        self::assertEquals(1, $violations->count());
    }

    public function test_a_namespace_with_a_wildcard_does_not_match_a_longer_name(): void
    {
        $notDependsOnTheseNamespaces = new NotDependsOnTheseNamespaces(['App\*\Infrastructure']);

        $classDescription = ClassDescription::getBuilder('App\Billing\Domain\Invoice', 'src/Invoice.php')
            ->addDependency(new ClassDependency('App\Billing\InfrastructureLegacy\Repository', 10))
            ->build();

        $violations = new Violations();
        $notDependsOnTheseNamespaces->evaluate($classDescription, $violations, 'the domain must not know about infrastructure');

        self::assertEquals(0, $violations->count());
    }

    public function test_the_escape_hatch_takes_a_pattern_like_every_other_namespace(): void
    {
        $notDependsOnTheseNamespaces = new NotDependsOnTheseNamespaces(['Vendor'], ['Vendor\*\Legacy']);

        $classDescription = ClassDescription::getBuilder('App\Billing\Domain\Invoice', 'src/Invoice.php')
            ->addDependency(new ClassDependency('Vendor\Acme\Legacy\Thing', 10))
            ->build();

        $violations = new Violations();
        $notDependsOnTheseNamespaces->evaluate($classDescription, $violations, 'we accept this known exception');

        self::assertEquals(0, $violations->count());
    }
}
