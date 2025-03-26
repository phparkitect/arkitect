<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class PatternString
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function matches(string $pattern): bool
    {
        if ('' === $pattern) {
            return false;
        }

        if (!$this->containsWildcard($pattern)) {
            $slashTerminatedPattern = str_ends_with($pattern, '\\') ? $pattern : $pattern.'\\';
            $isInThisNamespace = str_starts_with($this->value, $slashTerminatedPattern);
            $isThisClass = $this->value == $pattern;

            return $isInThisNamespace || $isThisClass;
        }

        return fnmatch($pattern, $this->value, \FNM_NOESCAPE);
    }

    public function toString(): string
    {
        return $this->value;
    }

    private function containsWildcard(string $pattern): bool
    {
        return
            str_contains($pattern, '*')
            || str_contains($pattern, '?')
            || str_contains($pattern, '.')
            || str_contains($pattern, '[');
    }
}
