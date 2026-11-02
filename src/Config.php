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
 * of those classes rules may judge is the dependency directories, `vendor`
 * unless the project says otherwise.
 *
 * Immutable, like everything else that is built up a piece at a time here,
 * and the `with` prefix is what says so: a `phparkitect.php` that writes
 * `$config->withBaseline(...)` and drops the result has lost it, and a name
 * reading as a command would have hidden that.
 */
final class Config
{
    private function __construct(
        private readonly string $root,
        private readonly Rules $rules,
        private readonly TargetPhpVersion $targetPhpVersion,
        private readonly DependencyDirectories $dependencies,
        private readonly ?string $baseline,
    ) {
    }

    public static function create(string $root): self
    {
        if (!is_dir($root)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a directory.', $root));
        }

        return new self(
            root: $root,
            rules: new Rules(),
            // unset means the interpreter running arkitect, which is what nearly
            // every project wants and what #650 says to pin when it isn't
            targetPhpVersion: TargetPhpVersion::current(),
            dependencies: new DependencyDirectories('vendor'),
            baseline: null,
        );
    }

    public function root(): string
    {
        return $this->root;
    }

    public function rules(): Rules
    {
        return $this->rules;
    }

    /** The version of PHP the *analysed* project targets, not the one running us. */
    public function targetPhpVersion(): TargetPhpVersion
    {
        return $this->targetPhpVersion;
    }

    /** The directories holding code the project did not write. */
    public function dependencies(): DependencyDirectories
    {
        return $this->dependencies;
    }

    /** Relative to the root, and null when the project keeps no baseline. */
    public function baseline(): ?string
    {
        return $this->baseline;
    }

    /** @param list<Rule> $rules array, not a variadic, because this is what a config file writes */
    public function add(array $rules): self
    {
        return new self(
            root: $this->root,
            rules: $this->rules->with(...$rules),
            targetPhpVersion: $this->targetPhpVersion,
            dependencies: $this->dependencies,
            baseline: $this->baseline,
        );
    }

    public function withBaseline(string $path): self
    {
        return new self(
            root: $this->root,
            rules: $this->rules,
            targetPhpVersion: $this->targetPhpVersion,
            dependencies: $this->dependencies,
            baseline: $path,
        );
    }

    /** For the commands that need to see what a baseline is currently hiding. */
    public function withoutBaseline(): self
    {
        return new self(
            root: $this->root,
            rules: $this->rules,
            targetPhpVersion: $this->targetPhpVersion,
            dependencies: $this->dependencies,
            baseline: null,
        );
    }

    public function withTargetPhpVersion(string $version): self
    {
        return new self(
            root: $this->root,
            rules: $this->rules,
            targetPhpVersion: TargetPhpVersion::create($version),
            dependencies: $this->dependencies,
            baseline: $this->baseline,
        );
    }

    /**
     * Replaces the default rather than adding to it, so `vendor` is written
     * out again when a monorepo adds its per-package one beside it: a list
     * that reads as the whole answer is one a reader can check against the
     * tree.
     */
    public function withDependencies(string ...$directories): self
    {
        return new self(
            root: $this->root,
            rules: $this->rules,
            targetPhpVersion: $this->targetPhpVersion,
            dependencies: new DependencyDirectories(...$directories),
            baseline: $this->baseline,
        );
    }
}
