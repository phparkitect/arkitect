<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Evaluate\NamespacePattern;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class ResideInOneOfTheseNamespaces implements Selector
{
    /** @var list<NamespacePattern> */
    private readonly array $patterns;

    /** @param list<string> $namespaces */
    public function __construct(array $namespaces)
    {
        $this->patterns = array_map(static fn (string $n) => new NamespacePattern($n), $namespaces);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        foreach ($this->patterns as $pattern) {
            if ($pattern->contains($class->fqcn)) {
                return Selection::Yes;
            }
        }

        return Selection::No;
    }
}
