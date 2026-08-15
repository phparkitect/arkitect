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
    public readonly string $fqcn;

    /** Kept so the derived accessors don't re-validate the name on every call. */
    private readonly Fqcn $name;

    public function __construct(
        string $fqcn,
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
        // the string ClassGraph indexes on, so it gets the same rule and the
        // same normalization every other name in the codebase gets
        $this->name = new Fqcn($fqcn);
        $this->fqcn = $this->name->toString();
    }

    /** The declared name without its namespace. */
    public function shortName(): string
    {
        return $this->name->shortName();
    }

    /** Empty for a class declared in the global namespace. */
    public function namespaceName(): string
    {
        return $this->name->namespaceName();
    }
}
