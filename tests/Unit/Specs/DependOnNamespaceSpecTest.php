<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Specs;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DependOnNamespaceSpec;
use PHPUnit\Framework\TestCase;

class DependOnNamespaceSpecTest extends TestCase
{
    public function test_it_should_return_false_if_class_not_depend_on_namespace(): void
    {
        $dependOnNamespaceSpec = new DependOnNamespaceSpec('foo');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertFalse($dependOnNamespaceSpec->apply($classDescription));
    }

    public function test_it_should_return_true_if_class_depend_on_namespace(): void
    {
        $dependOnNamespaceSpec = new DependOnNamespaceSpec('foo');
        $classDependency = new ClassDependency('foo\OtherClass', 100);

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('foo\HappyIsland'),
            [
            $classDependency,
        ],
            []
        );

        $this->assertTrue($dependOnNamespaceSpec->apply($classDescription));
    }
}
