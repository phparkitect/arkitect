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

    /**
     * Whether the short class name matches the pattern, e.g. '*Controller'.
     */
    public function classMatches(string $pattern): bool
    {
        return Pattern::fromString($pattern)->matches($this->className);
    }

    /**
     * Whether the class is the one the pattern denotes, or resides in a
     * namespace it denotes.
     *
     * Matching is recursive: the pattern is tried against the class itself and
     * then against every namespace the class lives in, so 'App\Domain' matches
     * 'App\Domain\Event\UserRegistered' through its namespace 'App\Domain', and
     * 'App\*\Infrastructure' matches 'App\Billing\Infrastructure\Repository'
     * the same way. A pattern only ever matches a whole name, so 'App\Foo'
     * does not reach into 'App\FooBar'.
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

    /**
     * Every namespace the class resides in, from the closest one to the root:
     * 'App\Billing\Domain\Invoice' lives in 'App\Billing\Domain', in
     * 'App\Billing' and in 'App'.
     *
     * @return list<string>
     */
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
