<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Parser\Fqcn;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;

/**
 * Follows the `extends` chain only, so an implemented interface does not
 * select — that is IsA's question.
 */
final class Extend implements Selector
{
    private readonly string $target;

    public function __construct(string $target)
    {
        $this->target = (new Fqcn($target))->toString();
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        return match ($classGraph->hasAncestor($class->fqcn, $this->target)) {
            Membership::Yes => Selection::Yes,
            Membership::No => Selection::No,
            Membership::Unknown => Selection::Unresolved,
        };
    }
}
