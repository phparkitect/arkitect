<?php

declare(strict_types=1);

namespace ArkitectTests\unit\Specs;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\ResideInNamespaceSpec;
use PHPUnit\Framework\TestCase;

class ResideInNamespaceSpecTest extends TestCase
{
    public function test_it_should_return_false_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInNamespaceSpec('MyNamespace');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('AnotherNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertFalse($haveNameMatching->apply($classDescription));
    }

    public function test_it_should_return_true_if_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInNamespaceSpec('MyNamespace');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('MyNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertTrue($haveNameMatching->apply($classDescription));
    }
}
