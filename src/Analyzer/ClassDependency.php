<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDependency
{
    private int $line;

    private FullyQualifiedClassName $FQCN;

    public function __construct(string $FQCN, int $line)
    {
        $this->line = $line;
        $this->FQCN = FullyQualifiedClassName::fromString($FQCN);
    }

    public function matches(string $pattern): bool
    {
        return $this->FQCN->matches($pattern);
    }

    public function matchesOneOf(string ...$patterns): bool
    {
        return $this->FQCN->matchesOneOf(...$patterns);
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getFQCN(): FullyQualifiedClassName
    {
        return $this->FQCN;
    }
}
