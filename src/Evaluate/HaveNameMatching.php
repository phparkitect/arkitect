<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class HaveNameMatching implements Expression, Selector
{
    private readonly Pattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new Pattern($pattern);
    }

    public function matches(ParsedClass $class, ClassGraph $classGraph): bool
    {
        return $this->pattern->matches($class->shortName());
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        if ($this->matches($class, $classGraph)) {
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
