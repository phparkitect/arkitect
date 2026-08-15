<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Selector\Selector;
use Arkitect\Evaluate\Selector\Selectors;

/**
 * A rule being written. It exists so that `Rule` is never a half-built
 * object: the incomplete states have their own types, and `because()` is
 * the only way out of them.
 */
final class RuleDraft
{
    private function __construct(private readonly Selectors $selectors)
    {
    }

    public static function start(): self
    {
        return new self(new Selectors());
    }

    public function that(Selector $selector): self
    {
        return new self($this->selectors->with($selector));
    }

    public function andThat(Selector $selector): self
    {
        return $this->that($selector);
    }

    public function should(Constraint $constraint): UnexplainedRule
    {
        return new UnexplainedRule($this->selectors, $constraint);
    }
}
