<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDependency
{
    private int $line;

    private \Arkitect\Analyzer\FullyQualifiedClassName $FQCN;

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
        foreach ($patterns as $pattern) {
            if (!$this->FQCN->matches($pattern)) {
                return false;
            }
        }

        return true;
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
