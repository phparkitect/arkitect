<?php

declare(strict_types=1);

namespace ArkitectTests\unit\Specs;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DoNotDependOnClassSpec;
use PHPUnit\Framework\TestCase;

class DoNotDependOnClassSpecTest extends TestCase
{
    public function test_it_should_return_true_if_class_do_not_depend_on_class(): void
    {
        $dependOnClassSpec = new DoNotDependOnClassSpec('foo');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertTrue($dependOnClassSpec->apply($classDescription));
    }

    public function test_it_should_return_false_if_class_do_not_depend_on_class(): void
    {
        $dependOnClassSpec = new DoNotDependOnClassSpec('OtherClass');
        $classDependency = new ClassDependency('OtherClass', 100);

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [
                $classDependency,
            ],
            []
        );

        $this->assertFalse($dependOnClassSpec->apply($classDescription));
    }
}
