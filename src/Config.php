<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\Rule;

/**
 * What the user writes, and the only place that answers "what is this run
 * about". Reached through `Config::create()->root(...)`, so a config
 * without a root is not representable.
 *
 * Everything under the root is *parsed*, because inheritance cannot be
 * resolved otherwise — a project class extending a vendor class needs the
 * vendor class's own ancestors (see ARCHITECTURE.md, stage 2). Everything
 * under the root except `vendor/` is *checked*. Those are two different
 * questions, and answering them with one scope is what would make a config
 * that forgets a namespace selector report thousands of violations in code
 * its author cannot change.
 */
final class Config
{
    private const NOT_YOURS = 'vendor/';

    /**
     * @param list<Rule> $rules
     *
     * @internal use Config::create()->root()
     */
    public function __construct(
        public readonly string $root,
        public readonly array $rules,
    ) {
        if (!is_dir($root)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a directory.', $root));
        }
    }

    public static function create(): ConfigDraft
    {
        return new ConfigDraft();
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

    /**
     * $path is relative to the root, which is also what a violation reports,
     * so both agree by construction.
     */
    public function checks(string $path): bool
    {
        return !str_starts_with($path, self::NOT_YOURS);
    }
}
