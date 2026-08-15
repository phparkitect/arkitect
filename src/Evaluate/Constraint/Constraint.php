<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Outcome;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * What a rule requires of the classes it selected.
 *
 * An `Outcome` and not `Violations`: what a class got wrong and what could
 * not be determined about it are different claims, and only the first may
 * reach a baseline.
 *
 * `ClassGraph` is a parameter and not a constructor dependency because the
 * config builds constraints before anything has been parsed.
 */
interface Constraint
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome;
}
