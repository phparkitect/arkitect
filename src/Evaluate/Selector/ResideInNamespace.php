<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Evaluate\NamespacePattern;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class ResideInNamespace implements Selector
{
    private readonly NamespacePattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new NamespacePattern($pattern);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        // reads the name only, so it always has a definitive answer
        return $this->pattern->contains($class->fqcn) ? Selection::Yes : Selection::No;
    }
}
