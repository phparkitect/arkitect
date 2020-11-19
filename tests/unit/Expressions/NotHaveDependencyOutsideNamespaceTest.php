<?php

declare(strict_types=1);

namespace ArkitectTests\unit\Expressions;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\NotHaveDependencyOutsideNamespace;
use PHPUnit\Framework\TestCase;

class NotHaveDependencyOutsideNamespaceTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $namespace = 'myNamespace';
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace($namespace);
        $classDescription = new ClassDescription(
            'full/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $violationError = $notHaveDependencyOutsideNamespace->describe($classDescription);

        $this->assertEquals('HappyIsland depends on classes outside in namespace '.$namespace, $violationError);
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace');
        $classDescription = new ClassDescription(
            'full/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertTrue($notHaveDependencyOutsideNamespace->evaluate($classDescription));
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('myNamespace');
        $classDescription = new ClassDescription(
            'full/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [new ClassDependency('myNamespace', 100)],
            []
        );

        $this->assertFalse($notHaveDependencyOutsideNamespace->evaluate($classDescription));
    }
}
