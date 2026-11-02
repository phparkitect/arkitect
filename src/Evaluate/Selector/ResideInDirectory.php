<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Evaluate\DirectoryPattern;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * Where a class is written, for the cases where directory and namespace
 * disagree: generated code, monorepo packages, a legacy tree. A namespace
 * selector cannot reach those, since the thing that tells them apart is
 * not in the name.
 */
final class ResideInDirectory implements Selector
{
    private readonly DirectoryPattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new DirectoryPattern($pattern);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        // reads the file path only, so it always has a definitive answer
        return $this->pattern->contains($class->filePath) ? Selection::Yes : Selection::No;
    }
}
