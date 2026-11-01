<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\NamespacePattern;
use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class ResideInNamespace implements Constraint
{
    private readonly NamespacePattern $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = new NamespacePattern($pattern);
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome
    {
        if ($this->matches($class)) {
            return new Outcome();
        }

        return new Outcome(new Violations(
            Violation::create($class, self::class, \sprintf('does not reside in %s', $this->pattern->toString()), $this->pattern->toString()),
        ));
    }

    private function matches(ParsedClass $class): bool
    {
        return $this->pattern->contains($class->fqcn);
    }
}
