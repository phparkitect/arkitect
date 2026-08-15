<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Parser\Fqcn;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;

/**
 * Transitive, like its constraint counterpart: a class inheriting the
 * interface from its parent does implement it. There is no Depth here yet —
 * selecting on the shape of a declaration rather than on the resulting type
 * is a rule nobody has asked for.
 */
final class Implement implements Selector
{
    private readonly string $target;

    public function __construct(string $target)
    {
        $this->target = (new Fqcn($target))->toString();
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
