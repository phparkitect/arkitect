<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Evaluate\Pattern;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class ResideInNamespace implements Selector
{
    private readonly Pattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new Pattern($pattern);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): bool
    {
        return $this->pattern->matches($class->fqcn);
    }
}
