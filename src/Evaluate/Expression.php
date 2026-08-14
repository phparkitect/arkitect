<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * `ClassGraph` is a parameter rather than a constructor dependency because
 * expressions are built by the config, which is read before anything is
 * parsed — the graph doesn't exist yet at construction time. Expressions
 * that answer from the declaration alone ignore it.
 *
 * `appliesTo()` is deliberately absent: its meaning is still open (it means
 * "excluded from the selector" in `that()` but "vacuously holds" in
 * `should()`, and has to reconcile with zero-matched being a visibly
 * different outcome from zero-violations). Adding it now would be guessing
 * at semantics the report depends on — see ARCHITECTURE.md, Open.
 */
interface Expression
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations;
}
