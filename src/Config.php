<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\Rule;

/**
 * What the user writes, and the only place that answers "what is this run
 * about".
 *
 * Holds what the user declared and nothing else. Everything under the root
 * is parsed; which of those classes rules may judge is `Codebase`'s
 * question, not a declaration anyone made here.
 */
final class Config
{
    /**
     * The root is a constructor argument, not a fluent step: it is required,
     * and PHP already refuses to build an object without a required
     * argument. Optional settings are the fluent ones, so a config file
     * shows at a glance what has to be given and what does not.
     *
     * @param list<Rule> $rules
     */
    public function __construct(
        public readonly string $root,
        public readonly array $rules = [],
    ) {
        if (!is_dir($root)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a directory.', $root));
        }
    }

    public static function create(string $root): self
    {
        return new self($root);
    }

    /** @param list<Rule> $rules */
    public function add(array $rules): self
    {
        foreach ($rules as $rule) {
            if (!$rule instanceof Rule) {
                throw new \InvalidArgumentException(\sprintf('Expected a rule, got %s.', get_debug_type($rule)));
            }
        }

        return new self($this->root, [...$this->rules, ...array_values($rules)]);
    }
}
