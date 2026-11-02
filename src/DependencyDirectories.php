<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\DirectoryPattern;
use Arkitect\Parser\ParsedClass;

/**
 * The directories holding code the project did not write. Everything under
 * the root is parsed, because inheritance cannot be resolved without the
 * ancestors; this says which of those classes rules are allowed to judge.
 *
 * A pattern is anchored at the root, so `vendor` is the project's own and
 * not a nested one — a monorepo package's, or composer-bin-plugin's, which
 * no entry covers today.
 */
final class DependencyDirectories
{
    /** @var list<DirectoryPattern> */
    private readonly array $directories;

    public function __construct(string ...$directories)
    {
        $this->directories = array_values(array_map(
            static fn (string $directory) => new DirectoryPattern($directory),
            $directories
        ));
    }

    public function contains(string $filePath): bool
    {
        foreach ($this->directories as $directory) {
            if ($directory->contains($filePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<ParsedClass> $classes everything parsed
     *
     * @return list<ParsedClass> what rules are allowed to judge
     */
    public function ownClassesIn(array $classes): array
    {
        return array_values(array_filter(
            $classes,
            fn (ParsedClass $class) => !$this->contains($class->filePath)
        ));
    }
}
