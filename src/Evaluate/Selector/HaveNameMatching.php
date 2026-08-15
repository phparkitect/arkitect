<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Evaluate\Pattern;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class HaveNameMatching implements Selector
{
    private readonly Pattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new Pattern($pattern);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        // reads the name only, so it always has a definitive answer
        return $this->pattern->matches($class->shortName()) ? Selection::Yes : Selection::No;
    }
}
