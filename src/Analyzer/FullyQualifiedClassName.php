<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class FullyQualifiedClassName
{
    /** @var PatternString */
    private $fqcnString;

    /** @var PatternString */
    private $namespace;

    /** @var PatternString */
    private $class;

    private function __construct(PatternString $fqcnString, PatternString $namespace, PatternString $class)
    {
        $this->fqcnString = $fqcnString;
        $this->namespace = $namespace;
        $this->class = $class;
    }

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

    public function namespace(): string
    {
        return $this->namespace->toString();
    }

    public static function fromString(string $fqcn): self
    {
        $validFqcn = '/^[a-zA-Z_\x7f-\xff\\\\][a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]$/';

        if (!(bool) preg_match($validFqcn, $fqcn)) {
            throw new \RuntimeException("$fqcn is not a valid namespace definition");
        }

        $pieces = explode('\\', $fqcn);
        $piecesWithoutEmpty = array_filter($pieces);
        $className = array_pop($piecesWithoutEmpty);
        $namespace = implode('\\', $piecesWithoutEmpty);

        return new self(new PatternString($fqcn), new PatternString($namespace), new PatternString($className));
    }
}
