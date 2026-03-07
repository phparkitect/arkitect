<?php

declare(strict_types=1);

namespace Arkitect\Tests\Utils;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\ClassHierarchyResolver;
use PHPUnit\Framework\TestCase;

trait MockHierarchyResolver
{
    /**
     * @param list<string> $parents
     * @param list<string> $interfaces
     * @param list<string> $traits
     */
    protected function createMockResolver(
        array $parents = [],
        array $interfaces = [],
        array $traits = [],
    ): ClassHierarchyResolver {
        /** @var TestCase $this */
        $resolver = $this->createStub(ClassHierarchyResolver::class);
        $resolver->method('getParentClassNames')->willReturn($parents);
        $resolver->method('getInterfaceNames')->willReturn($interfaces);
        $resolver->method('getTraitNames')->willReturn($traits);

        return $resolver;
    }

    protected function createBuilder(): ClassDescriptionBuilder
    {
        return new ClassDescriptionBuilder($this->createMockResolver());
    }
}
