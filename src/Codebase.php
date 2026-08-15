<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\ParseResult;
use Arkitect\Resolve\ClassGraph;

/**
 * One parse, two views of it: names resolve against everything parsed,
 * while rules may only judge the project's own code.
 *
 * The `vendor/` rule is here and not in `Config` because nobody declared
 * it. Move it there if a project ever needs to override it.
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
