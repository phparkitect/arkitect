<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class FullyQualifiedClassName
{
    private string $fqcn;

    private string $namespace;

    private string $className;

    private function __construct(string $fqcn, string $namespace, string $className)
    {
        $this->fqcn = $fqcn;
        $this->namespace = $namespace;
        $this->className = $className;
    }

    public function toString(): string
    {
        return $this->fqcn;
    }

    public function classMatches(string $pattern): bool
    {
        return Pattern::fromString($pattern)->matches($this->className);
    }

    /**
     * Matching is recursive: the pattern is tried against the class and against
     * every namespace it resides in, so 'App\*\Infrastructure' matches
     * 'App\Billing\Infrastructure\Repository' through 'App\Billing\Infrastructure'.
     */
    public function matches(string $pattern): bool
    {
        $pattern = Pattern::fromString($pattern);

        if ($pattern->matches($this->fqcn)) {
            return true;
        }

        foreach ($this->namespaces() as $namespace) {
            if ($pattern->matches($namespace)) {
                return true;
            }
        }

        return false;
    }

    public function matchesOneOf(string ...$patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->matches($pattern)) {
                return true;
            }
        }

        return false;
    }

    public function className(): string
    {
        return $this->className;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public static function fromString(string $fqcn): self
    {
        $validFqcn = '/^[a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]$/';

        if (!(bool) preg_match($validFqcn, $fqcn)) {
            throw new \RuntimeException("$fqcn is not a valid namespace definition");
        }

        $pieces = explode('\\', $fqcn);
        $piecesWithoutEmpty = array_filter($pieces);
        $className = array_pop($piecesWithoutEmpty);
        $namespace = implode('\\', $piecesWithoutEmpty);

        // $className can't be null: the regex above rejects an empty or trailing-backslash $fqcn
        /** @psalm-suppress PossiblyNullArgument */
        return new self($fqcn, $namespace, $className);
    }

    /** @return list<string> from the closest namespace to the root */
    private function namespaces(): array
    {
        $namespaces = [];
        $namespace = $this->namespace;

        while ('' !== $namespace) {
            $namespaces[] = $namespace;

            $lastSeparator = strrpos($namespace, '\\');
            $namespace = false === $lastSeparator ? '' : substr($namespace, 0, $lastSeparator);
        }

        return $namespaces;
    }
}
