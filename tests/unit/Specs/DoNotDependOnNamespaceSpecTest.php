<?php

declare(strict_types=1);


namespace ArkitectTests\unit\Specs;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DoNotDependOnNamespaceSpec;
use PHPUnit\Framework\TestCase;

class DoNotDependOnNamespaceSpecTest extends TestCase
{
    public function test_it_should_return_true_if_class_do_not_depend_on_namespace(): void
    {
        $dependOnNamespaceSpec = new DoNotDependOnNamespaceSpec('foo');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertTrue($dependOnNamespaceSpec->apply($classDescription));
    }

    public function test_it_should_return_false_if_class_do_not_depend_on_namespace(): void
    {
        $dependOnNamespaceSpec = new DoNotDependOnNamespaceSpec('foo');
        $classDependency = new ClassDependency('foo\OtherClass', 100);

        $classDescription = new ClassDescription(
            '/foo',
            FullyQualifiedClassName::fromString('foo\HappyIsland'),
            [
                $classDependency
            ],
            []
        );

        $this->assertFalse($dependOnNamespaceSpec->apply($classDescription));
    }
}
