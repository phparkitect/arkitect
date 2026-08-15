<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * What a rule talks about, as opposed to what it requires. A selector can
 * never produce a violation — a class it doesn't match is simply not this
 * rule's business — which is why nothing in this namespace knows Violation
 * exists.
 *
 * Selectors and constraints are separate types, and separate classes even
 * where they share a name. v1 used one type in both positions and needed
 * appliesTo() to tell them apart at runtime, which is where that method's
 * dual meaning came from: "excluded from the selector" in that(),
 * "vacuously true" in should(). Here the position has a type.
 *
 * The split also lets the same question have two different answers: an
 * unresolvable ancestor chain is a violation for a constraint, while for a
 * selector it is a decision about whether to include the class at all.
 */
interface Selector
{
    public function matches(ParsedClass $class, ClassGraph $classGraph): bool;
}
