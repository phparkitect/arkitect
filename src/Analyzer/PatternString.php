<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class PatternString
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function matches(string $pattern): bool
    {
        if ('' === $pattern) {
            return false;
        }

        // se è una stringa senza wildcard ed è il prefisso faccio match
        if ((
            !str_contains($pattern, '*') &&
            !str_contains($pattern, '?') &&
            !str_contains($pattern, '.') &&
            !str_contains($pattern, '[')
        ) && str_starts_with($this->value, $pattern)) {
            return true;
        }

        return fnmatch($pattern, $this->value, FNM_NOESCAPE);
    }

    public function explode(string $delimiter): array
    {
        return explode($delimiter, $this->value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
