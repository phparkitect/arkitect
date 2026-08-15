<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Selector\Selectors;

/**
 * A rule that knows what it wants but not why. `because()` is the only way
 * out, so a rule cannot reach a run without a reason to give when it fails.
 *
 * There is no `andShould()`, unlike v1: a rule states one requirement, the
 * way a good test makes one assertion. Two requirements are two rules, each
 * with its own reason — which is also what keeps a reason honest, since one
 * sentence explaining two unrelated constraints explains neither.
 */
final class UnexplainedRule
{
    public function __construct(
        private readonly Selectors $selectors,
        private readonly Constraint $constraint,
    ) {
    }

    public function because(string $reason): Rule
    {
        return new Rule($this->selectors, $this->constraint, $reason);
    }
}
