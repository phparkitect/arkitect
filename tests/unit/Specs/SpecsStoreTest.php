<?php

declare(strict_types=1);

namespace ArkitectTests\unit\Specs;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DependOnClassSpec;
use Arkitect\Specs\SpecsStore;
use PHPUnit\Framework\TestCase;

class SpecsStoreTest extends TestCase
{
    public function test_return_false_if_not_all_specs_are_matched(): void
    {
        $specStore = new SpecsStore();
        $specStore->add(
            new DependOnClassSpec('Foo')
        );

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('MyNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertFalse($specStore->allSpecsAreMatchedBy($classDescription));
    }

    public function test_return_true_if_all_specs_are_matched(): void
    {
        $specStore = new SpecsStore();
        $specStore->add(
            new DependOnClassSpec('Foo')
        );

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('MyNamespace\HappyIsland'),
            [
                new ClassDependency('Foo', 100),
            ],
            []
        );

        $this->assertTrue($specStore->allSpecsAreMatchedBy($classDescription));
    }
}
