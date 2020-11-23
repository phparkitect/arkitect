<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ResideInNamespace;
use PHPUnit\Framework\TestCase;

class ResideInNamespaceTest extends TestCase
{
    public function shouldMatchNamespacesProvider(): array
    {
        return [
            ['Food\Vegetables', 'Food\Vegetables\Carrot', 'matches a class in the root namespace'],
            ['Food\Vegetables', 'Food\Vegetables\Roots\Carrot', 'matches a class in a child namespace'],
            ['Food\Vegetables', 'Food\Vegetables\Roots\Orange\Carrot', 'matches a class in a child of a child namespace'],
        ];
    }

    /**
     * @dataProvider shouldMatchNamespacesProvider
     *
     * @param mixed $expectedNamespace
     * @param mixed $actualFQCN
     */
    public function test_it_should_match_namespace_and_descendants($expectedNamespace, $actualFQCN): void
    {
        $haveNameMatching = new ResideInNamespace($expectedNamespace);

        $classDesc = ClassDescription::build($actualFQCN, '')->get();

        $this->assertNotTrue($haveNameMatching->evaluate($classDesc));
    }

    public function test_it_should_return_false_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInNamespace('MyNamespace');

        $classDescription = new ClassDescription(
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
            FullyQualifiedClassName::fromString('MyNamespace\HappyIsland'),
            [],
            []
        );

        $this->assertFalse($haveNameMatching->evaluate($classDescription));
    }
}
