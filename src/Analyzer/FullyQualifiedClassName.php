<?php

namespace Arkitect\Analyzer;

class FullyQualifiedClassName
{
    /**
     * @var PatternString
     */
    private $fqcnString;
    /**
     * @var PatternString
     */
    private $namespace;
    /**
     * @var PatternString
     */
    private $class;

    public function toString(): string
    {
        return $this->fqcnString->toString();
    }

    public function classMatches(string $pattern): bool
    {
        return $this->class->matches($pattern);
    }

    public function namespaceMatches(string $pattern): bool
    {
        return $this->namespace->matches($pattern);
    }

    public function matches(string $pattern): bool
    {
        return $this->fqcnString->matches($pattern);
    }

    public function className(): string
    {
        return $this->class->toString();
    }

    public static function fromString(string $fqcn): self
    {
        $validFqcn = '/^[a-zA-Z_\x7f-\xff\\\\][a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]$/';

        if (!(bool) preg_match($validFqcn, $fqcn)) {
            throw new \RuntimeException("$fqcn is not a valid namespace definition");
        }

        $pieces = explode('\\', $fqcn);
        $className = array_pop($pieces);
        $namespace = implode('\\', $pieces);

        $f = new self();
        $f->fqcnString = new PatternString($fqcn);
        $f->namespace = new PatternString($namespace);
        $f->class = new PatternString($className);

        return $f;
    }
}