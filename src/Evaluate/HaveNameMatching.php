<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class HaveNameMatching implements Expression
{
    private readonly Pattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new Pattern($pattern);
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        if ($this->pattern->matches($class->shortName())) {
            return new Violations();
        }

        return new Violations([
            Violation::create(
                $class,
                self::class,
                \sprintf('does not have a name matching %s', $this->pattern->toString())
            ),
        ]);
    }
}
