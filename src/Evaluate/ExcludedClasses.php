<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\Fqcn;

/**
 * The classes a rule is written not to be about, named one by one.
 *
 * Whole names only, and that is the point: a pattern may legitimately match
 * nothing, so a pattern that excludes nothing cannot be told apart from a
 * typo, while a name no class in the codebase answers to is always a
 * mistake and can be reported.
 */
final class ExcludedClasses implements \Countable
{
    /** @var list<string> */
    private readonly array $names;

    public function __construct(string ...$names)
    {
        $this->names = array_values(array_map(
            static fn (string $name) => (new Fqcn($name))->toString(),
            $names
        ));
    }

    public function contains(string $fqcn): bool
    {
        return \in_array($fqcn, $this->names, true);
    }

    /**
     * @param list<string> $fqcns
     *
     * @return list<string>
     */
    public function findMissingFrom(array $fqcns): array
    {
        return array_values(array_diff($this->names, $fqcns));
    }

    public function count(): int
    {
        return \count($this->names);
    }
}
