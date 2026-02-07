<?php

declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Rules\DSL\ArchRule;

class UnusedRules implements \Countable, \IteratorAggregate
{
    /** @var array<ArchRule> */
    private $rules = [];

    public function add(ArchRule $rule): void
    {
        $this->rules[] = $rule;
    }

    public function count(): int
    {
        return \count($this->rules);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->rules);
    }

    /**
     * @return array<string>
     */
    public function describe(): array
    {
        $descriptions = [];

        foreach ($this->rules as $rule) {
            $descriptions[] = $rule->describe();
        }

        return $descriptions;
    }
}
