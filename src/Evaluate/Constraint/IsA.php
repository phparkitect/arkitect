<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;

final class IsA implements Constraint
{
    public function __construct(private readonly string $target)
    {
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome
    {
        return match ($classGraph->isA($class->fqcn, $this->target)) {
            Membership::Yes => new Outcome(),
            Membership::No => new Outcome(new Violations([
                Violation::create($class, self::class, \sprintf('is not a %s', $this->target)),
            ])),
            Membership::Unknown => Outcome::unresolved($class, \sprintf(
                'cannot be checked against %s: some ancestors were never parsed',
                $this->target
            )),
        };
    }
}
