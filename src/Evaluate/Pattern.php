<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * A class or namespace pattern: it matches a name exactly, or anything
 * beneath that name as a namespace. Only `*` and `?` are wildcards —
 * regexes are not accepted, and saying so at construction keeps a config
 * mistake from surfacing halfway through a run.
 *
 * Both halves apply whether or not the pattern has a wildcard, which is
 * the one deliberate divergence from v1: there, a wildcard pattern was
 * glob-matched against the whole name and lost the "anything beneath it"
 * half, so `App\*\Domain` silently matched nothing at all.
 */
final class Pattern
{
    private const ALLOWED = '/^([a-zA-Z0-9_\x80-\xff]|\\\\|\*|\?)+$/';

    private readonly string $value;

    public function __construct(string $value)
    {
        // a pattern can't be an Fqcn — it has wildcards — but it is written
        // against names that never carry a leading separator, so the same
        // normalization applies or `\App\Domain` would match nothing at all
        $value = str_starts_with($value, '\\') ? substr($value, 1) : $value;
        $this->value = $value;

        if (1 !== preg_match(self::ALLOWED, $value)) {
            throw new \InvalidArgumentException(\sprintf("'%s' is not a valid class or namespace pattern: only * and ? are wildcards.", $value));
        }
    }

    public function matches(string $name): bool
    {
        return $this->matchesExactly($name) || $this->matchesBeneath($name);
    }

    public function toString(): string
    {
        return $this->value;
    }

    private function matchesExactly(string $name): bool
    {
        return $this->hasWildcard()
            ? fnmatch($this->bare(), $name, \FNM_NOESCAPE)
            : $name === $this->bare();
    }

    private function matchesBeneath(string $name): bool
    {
        return $this->hasWildcard()
            ? fnmatch($this->bare().'\*', $name, \FNM_NOESCAPE)
            : str_starts_with($name, $this->bare().'\\');
    }

    private function bare(): string
    {
        return rtrim($this->value, '\\');
    }

    private function hasWildcard(): bool
    {
        return str_contains($this->value, '*') || str_contains($this->value, '?');
    }
}
