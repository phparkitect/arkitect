<?php

declare(strict_types=1);

namespace Arkitect\Parser;

/**
 * Everything a single class-like declaration says about itself. Nothing
 * here is resolved against other files and nothing here calls into the
 * PHP runtime — see ARCHITECTURE.md, stage 1.
 */
final class ParsedClass
{
    /**
     * @param list<TypeReference> $extends       direct parents (interfaces may have more than one)
     * @param list<TypeReference> $implements
     * @param list<TypeReference> $traits
     * @param list<TypeReference> $dependencies   every type name referenced anywhere in the
     *                                            declaration (includes extends/implements/traits/
     *                                            attributes too) — unfiltered, no core-class exclusion
     * @param list<TypeReference> $attributes
     * @param list<string>        $docBlocks      raw text, unparsed
     */
    public function __construct(
        public readonly string $fqcn,
        public readonly int $line,
        public readonly string $filePath,
        public readonly ClassKind $kind,
        public readonly array $extends,
        public readonly array $implements,
        public readonly array $traits,
        public readonly array $dependencies,
        public readonly array $attributes,
        public readonly array $docBlocks,
        public readonly bool $isFinal,
        public readonly bool $isReadonly,
        public readonly bool $isAbstract,
    ) {
    }
}
