<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Specs;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\HaveNameMatching;
use Arkitect\Specs\SpecsStore;
use PHPUnit\Framework\TestCase;

class SpecsStoreTest extends TestCase
{
    public function test_return_false_if_not_all_specs_are_matched(): void
    {
        $specStore = new SpecsStore();
        $specStore->add(
            new HaveNameMatching('Foo')
        );

        $classDescription = new ClassDescription(
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
            new HaveNameMatching('Happy*')
        );

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('MyNamespace\HappyIsland'),
            [
            new ClassDependency('Foo', 100),
        ],
            []
        );

        $this->assertTrue($specStore->allSpecsAreMatchedBy($classDescription));
    }
}
