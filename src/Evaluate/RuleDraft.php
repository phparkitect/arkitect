<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Selector\Selector;

/**
 * A rule being written. It exists so that `Rule` is never a half-built
 * object: the incomplete states have their own types, and `because()` is
 * the only way out of them.
 */
final class RuleDraft
{
    /** @param list<Selector> $selectors */
    private function __construct(private readonly array $selectors)
    {
    }

    public static function start(): self
    {
        return new self([]);
    }

    public function that(Selector $selector): self
    {
        return new self([...$this->selectors, $selector]);
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
