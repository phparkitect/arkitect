<?php

namespace Arkitect\Analyzer;

class ClassDependency
{
    private $line;

    private $FQCN;

    public function __construct(string $FQCN, string $line)
    {
        $this->line = $line;
        $this->FQCN = FullyQualifiedClassName::fromString($FQCN);
    }

    public function matches(string $pattern): bool
    {
        return $this->FQCN->matches($pattern);
    }

    public function getLine(): string
    {
        return $this->line;
    }

    public function getFQCN(): FullyQualifiedClassName
    {
        return $this->FQCN;
    }
}