<?php

declare(strict_types=1);


namespace ArkitectTests\unit\Specs;


use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DoNotImplementInterfaceSpec;
use PHPUnit\Framework\TestCase;

class DoNotImplementInterfaceSpecTest extends TestCase
{
    public function test_it_should_return_true_if_not_implement_interface(): void
    {
        $haveNameMatching = new DoNotImplementInterfaceSpec('MyInterface');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [
                FullyQualifiedClassName::fromString('AnotherInterface'),
            ]
        );

        $this->assertTrue($haveNameMatching->apply($classDescription));
    }

    public function test_it_should_return_false_if_implement_interface(): void
    {
        $haveNameMatching = new DoNotImplementInterfaceSpec('MyInterface');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [
                FullyQualifiedClassName::fromString('MyInterface'),
            ]
        );

        $this->assertFalse($haveNameMatching->apply($classDescription));
    }
}