<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Specs;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DoNotResideInNamespaceSpec;
use PHPUnit\Framework\TestCase;

class DoNotResideInNamespaceSpecTest extends TestCase
{
    public function test_it_should_return_true_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new DoNotResideInNamespaceSpec('MyNamespace');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('AnotherNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertTrue($haveNameMatching->apply($classDescription));
    }

    public function test_it_should_false_true_if_reside_in_namespace(): void
    {
        $haveNameMatching = new DoNotResideInNamespaceSpec('MyNamespace');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('MyNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertFalse($haveNameMatching->apply($classDescription));
    }
}
