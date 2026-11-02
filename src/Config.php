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
 * Holds what the user declared. Everything under the root is parsed; which
 * of those classes rules may judge is `$dependencies`, `vendor` unless the
 * project says otherwise.
 */
final class Config
{
    public Rules $rules;

    public TargetPhpVersion $targetPhpVersion;

    public DependencyDirectories $dependencies;

    /** Relative to the root, and null when the project keeps no baseline. */
    public ?string $baseline = null;

    public function __construct(public readonly string $root)
    {
        if (!is_dir($root)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a directory.', $root));
        }

        $this->rules = new Rules();
        $this->dependencies = new DependencyDirectories('vendor');
        // unset means the interpreter running arkitect, which is what nearly
        // every project wants and what #650 says to pin when it isn't
        $this->targetPhpVersion = TargetPhpVersion::current();
    }

    public static function create(string $root): self
    {
        return new self($root);
    }

    /** @param list<Rule> $rules array, not a variadic, because this is what a config file writes */
    public function add(array $rules): self
    {
        $this->rules = $this->rules->with(...$rules);

        return $this;
    }

    public function baseline(string $path): self
    {
        $this->baseline = $path;

        return $this;
    }

    /** For the commands that need to see what a baseline is currently hiding. */
    public function withoutBaseline(): self
    {
        $copy = clone $this;
        $copy->baseline = null;

        return $copy;
    }

    /**
     * The directories holding code the project did not write. Replaces the
     * default rather than adding to it, so `vendor` is written out again
     * when a monorepo adds its per-package one beside it: a list that reads
     * as the whole answer is one a reader can check against the tree.
     */
    public function dependencies(string ...$directories): self
    {
        $this->dependencies = new DependencyDirectories(...$directories);

        return $this;
    }

    /** The version of PHP the *analysed* project targets, not the one running us. */
    public function targetPhpVersion(string $version): self
    {
        $this->targetPhpVersion = TargetPhpVersion::create($version);

        return $this;
    }
}
