<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\ParseResult;
use Arkitect\Resolve\ClassGraph;

/**
 * One parse, two views of it, which is the whole reason this exists: names
 * resolve against everything that was parsed, while rules may only judge
 * the project's own code.
 *
 * Both are needed and they are not the same set. `vendor/` has to be parsed
 * or inheritance cannot be resolved — a project class extending a vendor
 * class needs that class's own ancestors. It must not be judged, or a
 * config that forgets a namespace selector reports thousands of violations
 * in code its author cannot change.
 *
 * The `vendor/` rule lives here rather than in `Config` because nobody
 * declared it: it is our policy, not the user's input. It moves to `Config`
 * the day it becomes something a project can override.
 */
final class Codebase
{
    private const DEPENDENCIES = 'vendor/';

    /**
     * @param list<ParsedClass> $ownClasses what rules are allowed to judge
     * @param ClassGraph        $graph      everything parsed, what names resolve against
     */
    private function __construct(
        public readonly array $ownClasses,
        public readonly ClassGraph $graph,
    ) {
    }

    public static function of(ParseResult $parsed): self
    {
        $own = array_values(array_filter(
            $parsed->classes,
            static fn (ParsedClass $class) => !str_starts_with($class->filePath, self::DEPENDENCIES)
        ));

        return new self($own, new ClassGraph(...$parsed->classes));
    }
}
