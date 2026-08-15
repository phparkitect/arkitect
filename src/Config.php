<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Rules;

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
    public function __construct(
        public readonly string $root,
        public readonly Rules $rules = new Rules(),
    ) {
        if (!is_dir($root)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a directory.', $root));
        }
    }

    public static function create(string $root): self
    {
        return new self($root);
    }

    /** @param list<Rule> $rules array, not a variadic, because this is what a config file writes */
    public function add(array $rules): self
    {
        return new self($this->root, $this->rules->with(...$rules));
    }
}
