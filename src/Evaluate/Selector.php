<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * What a rule talks about, as opposed to what it requires. A selector can
 * never produce a violation: a class it doesn't match is simply not this
 * rule's business.
 *
 * This is a separate contract from Expression on purpose. v1 used one type
 * in both positions and needed appliesTo() to tell them apart at runtime,
 * which is where its dual meaning came from — "excluded from the selector"
 * in that(), "vacuously true" in should(). Here the position has a type,
 * so nothing has to be inferred. Classes that make sense in both positions
 * (ResideInNamespace, HaveNameMatching) implement both.
 */
interface Selector
{
    public function matches(ParsedClass $class, ClassGraph $classGraph): bool;
}
