<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;

final class IsA implements Expression
{
    public function __construct(private readonly string $target)
    {
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        $detail = match ($classGraph->isA($class->fqcn, $this->target)) {
            Membership::Yes => null,
            Membership::No => \sprintf('is not a %s', $this->target),
            Membership::Unknown => \sprintf(
                'cannot be resolved against %s: some ancestors were never parsed',
                $this->target
            ),
        };

        if (null === $detail) {
            return new Violations();
        }

        return new Violations([Violation::create($class, self::class, $detail)]);
    }
}
