<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotHaveDependencyOutsideNamespaceTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $namespace = 'myNamespace';
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace($namespace);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notHaveDependencyOutsideNamespace->describe($classDescription, $because)->toString();

        self::assertEquals(
            'should not depend on classes outside namespace '.$namespace.' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDependency(new ClassDependency('myNamespace', 100))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDependency(new ClassDependency('myNamespace', 100))
            ->addDependency(new ClassDependency('another\class', 200))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_not_return_violation_error_if_dependency_excluded(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace', ['foo']);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDependency(new ClassDependency('foo', 100))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_automatically_exclude_php_core_classes(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDependency(new ClassDependency('another\class', 100))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }

    public function test_a_dependency_in_a_sub_namespace_of_a_wildcard_namespace_is_inside(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('App\*\Infrastructure');

        $classDescription = ClassDescription::getBuilder('App\Billing\Domain\Invoice', 'src/Invoice.php')
            ->addDependency(new ClassDependency('App\Billing\Infrastructure\DoctrineInvoiceRepository', 10))
            ->build();

        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, 'it must stay inside infrastructure');

        self::assertEquals(0, $violations->count());
    }

    public function test_the_escape_hatch_takes_a_pattern_like_every_other_namespace(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('App', ['Vendor\*\Legacy']);

        $classDescription = ClassDescription::getBuilder('App\Billing\Domain\Invoice', 'src/Invoice.php')
            ->addDependency(new ClassDependency('Vendor\Acme\Legacy\Thing', 10))
            ->build();

        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, 'we accept this known exception');

        self::assertEquals(0, $violations->count());
    }
}
