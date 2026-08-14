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
     * @param TypeReferences $extends      direct parents (interfaces may have more than one)
     * @param TypeReferences $dependencies every type name referenced anywhere in the declaration
     *                                     (includes extends/implements/traits/attributes too) —
     *                                     unfiltered, no core-class exclusion
     * @param list<string>   $docBlocks    raw text, unparsed
     */
    public function __construct(
        public readonly string $fqcn,
        public readonly int $line,
        public readonly string $filePath,
        public readonly ClassKind $kind,
        public readonly TypeReferences $extends,
        public readonly TypeReferences $implements,
        public readonly TypeReferences $traits,
        public readonly TypeReferences $dependencies,
        public readonly TypeReferences $attributes,
        public readonly array $docBlocks,
        public readonly bool $isFinal,
        public readonly bool $isReadonly,
        public readonly bool $isAbstract,
    ) {
    }

    /** The declared name without its namespace. */
    public function shortName(): string
    {
        $lastSeparator = strrpos($this->fqcn, '\\');

        return false === $lastSeparator ? $this->fqcn : substr($this->fqcn, $lastSeparator + 1);
    }
}
