<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Evaluate\Pattern;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * Everything outside a namespace. `that()` only narrows, so without this the
 * only way to say "all of it except one component" is to list the others by
 * hand and remember to update the list when one is added.
 */
final class NotResideInNamespace implements Selector
{
    private readonly Pattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new Pattern($pattern);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        // reads the name only, so it always has a definitive answer
        return $this->pattern->matches($class->fqcn) ? Selection::No : Selection::Yes;
    }
}
