<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Evaluate\PathPattern;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * Everything written outside a directory. `that()` only narrows, so a rule
 * about the codebase except its generated tree cannot be written by naming
 * the rest of it: this is what v1 spelled `excludePath()`, and a directory
 * is what tells generated and legacy code apart when the namespace does
 * not.
 */
final class NotResideInPath implements Selector
{
    private readonly PathPattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new PathPattern($pattern);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        // reads the file path only, so it always has a definitive answer
        return $this->pattern->contains($class->filePath) ? Selection::No : Selection::Yes;
    }
}
