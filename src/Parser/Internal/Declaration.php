<?php

declare(strict_types=1);

namespace Arkitect\Parser\Internal;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\TypeReference;
use PhpParser\Node;

/**
 * The per-kind differences (Class_/Interface_/Trait_/Enum_) collapsed into
 * one shape, so the rest of the walk doesn't need to know which kind it's
 * looking at. Built by ClassCollector::declarationOf(), consumed by
 * ClassCollector::findClasses() to build the eventual ParsedClass.
 *
 * @internal
 */
final class Declaration
{
    /**
     * @param list<Node>                $stmts
     * @param list<Node\AttributeGroup> $attrGroups
     * @param list<TypeReference>       $extends
     * @param list<TypeReference>       $implements
     */
    public function __construct(
        public readonly string $fqcn,
        public readonly array $stmts,
        public readonly array $attrGroups,
        public readonly ClassKind $kind,
        public readonly array $extends = [],
        public readonly array $implements = [],
        public readonly bool $isFinal = false,
        public readonly bool $isReadonly = false,
        public readonly bool $isAbstract = false,
    ) {
    }
}
