<?php

declare(strict_types=1);

namespace ArkitectTests\unit\Expressions;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ResideInNamespace;
use PHPUnit\Framework\TestCase;

class ResideInNamespaceTest extends TestCase
{
    public function test_it_should_return_false_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInNamespace('MyNamespace');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('AnotherNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertTrue($haveNameMatching->evaluate($classDescription));
    }

    public function test_it_should_return_true_if_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInNamespace('MyNamespace');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('MyNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertFalse($haveNameMatching->evaluate($classDescription));
    }
}
