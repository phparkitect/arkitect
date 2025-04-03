<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
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
            ->addDependency(new ClassDependency('\DateTime', 300))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, $because);

        self::assertEquals(2, $violations->count());
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

    public function test_it_should_not_return_violation_error_if_core_dependency_excluded(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace', [], true);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDependency(new ClassDependency('\DateTime', 100))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}
