<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Pattern;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class HaveNameMatching implements Constraint
{
    private readonly Pattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new Pattern($pattern);
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        if ($this->matches($class)) {
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

    private function matches(ParsedClass $class): bool
    {
        return $this->pattern->matches($class->shortName());
    }
}
