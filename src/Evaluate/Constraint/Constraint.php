<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * What a rule requires of the classes it selected. Returns what it found
 * rather than mutating an accumulator handed to it, which is what lets
 * Violations be immutable.
 *
 * A constraint is strictly richer than a predicate, which is why it is not
 * the same contract as Selector: DependOnlyOnTheseNamespaces reports one
 * violation per offending dependency, each on its own line, and no boolean
 * expresses that.
 *
 * `ClassGraph` is a parameter rather than a constructor dependency because
 * constraints are built by the config, which is read before anything is
 * parsed — the graph doesn't exist yet at construction time. Constraints
 * that answer from the declaration alone ignore it.
 */
interface Constraint
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations;
}
