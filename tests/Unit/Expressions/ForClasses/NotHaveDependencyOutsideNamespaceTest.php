<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotHaveDependencyOutsideNamespaceTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $namespace = 'myNamespace';
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace($namespace);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null
        );

        $violationError = $notHaveDependencyOutsideNamespace->describe($classDescription)->toString();

        $this->assertEquals('should not depend on classes outside in namespace '.$namespace, $violationError);
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace');
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [new ClassDependency('myNamespace', 100)],
            [],
            null
        );

        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations);
        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace');
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [new ClassDependency('myNamespace', 100), new ClassDependency('another\class', 200)],
            [],
            null
        );

        $violations = new Violations();
        $notHaveDependencyOutsideNamespace->evaluate($classDescription, $violations);
        self::assertNotEquals(0, $violations->count());
    }
}
