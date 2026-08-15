<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Rules;
use Arkitect\Parser\TargetPhpVersion;

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
    public readonly TargetPhpVersion $targetPhpVersion;

    public function __construct(
        public readonly string $root,
        public readonly Rules $rules = new Rules(),
        ?TargetPhpVersion $targetPhpVersion = null,
    ) {
        // unset means the interpreter running arkitect, which is what nearly
        // every project wants and what #650 says to pin when it isn't
        $this->targetPhpVersion = $targetPhpVersion ?? TargetPhpVersion::current();

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
        return new self($this->root, $this->rules->with(...$rules), $this->targetPhpVersion);
    }

    /** The version of PHP the *analysed* project targets, not the one running us. */
    public function targetPhpVersion(string $version): self
    {
        return new self($this->root, $this->rules, TargetPhpVersion::create($version));
    }
}
