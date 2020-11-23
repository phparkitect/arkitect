<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Specs;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\ImplementInterfaceSpec;
use PHPUnit\Framework\TestCase;

class ImplementInterfaceSpecTest extends TestCase
{
    public function test_it_should_return_false_if_not_implement_interface(): void
    {
        $haveNameMatching = new ImplementInterfaceSpec('MyInterface');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [
                FullyQualifiedClassName::fromString('AnotherInterface'),
            ]
        );

        $this->assertFalse($haveNameMatching->apply($classDescription));
    }

    public function test_it_should_return_true_if_implement_interface(): void
    {
        $haveNameMatching = new ImplementInterfaceSpec('MyInterface');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [
                FullyQualifiedClassName::fromString('MyInterface'),
            ]
        );

        $this->assertTrue($haveNameMatching->apply($classDescription));
    }
}
