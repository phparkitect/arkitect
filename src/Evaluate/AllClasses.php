<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Selector\Selector;

/**
 * A rule before anything has narrowed it, which is the only place `except()`
 * can be read: it takes classes away from all of them. After a `that()` the
 * same word would be read against whatever the selectors happen to be, and
 * what it took away would depend on the rule it was written in.
 */
final class AllClasses
{
    public function except(string ...$classes): RuleDraft
    {
        return RuleDraft::start(new ExcludedClasses(...$classes));
    }

    public function that(Selector $selector): RuleDraft
    {
        return RuleDraft::start(new ExcludedClasses())->that($selector);
    }

    public function should(Constraint $constraint): UnexplainedRule
    {
        return RuleDraft::start(new ExcludedClasses())->should($constraint);
    }
}
