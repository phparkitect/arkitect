<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Specs;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DependOnClassSpec;
use PHPUnit\Framework\TestCase;

class DependOnClassSpecTest extends TestCase
{
    public function test_it_should_return_false_if_class_not_depend_on_class(): void
    {
        $dependOnClassSpec = new DependOnClassSpec('foo');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertFalse($dependOnClassSpec->apply($classDescription));
    }

    public function test_it_should_return_true_if_class_depend_on_class(): void
    {
        $dependOnClassSpec = new DependOnClassSpec('OtherClass');
        $classDependency = new ClassDependency('OtherClass', 100);

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [
            $classDependency,
        ],
            []
        );

        $this->assertTrue($dependOnClassSpec->apply($classDescription));
    }
}
