<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;

/**
 * "Every class that is a X" — the shape most rules start from.
 */
final class IsA implements Selector
{
    public function __construct(private readonly string $target)
    {
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        return match ($classGraph->isA($class->fqcn, $this->target)) {
            Membership::Yes => Selection::Yes,
            Membership::No => Selection::No,
            Membership::Unknown => Selection::Unresolved,
        };
    }
}
