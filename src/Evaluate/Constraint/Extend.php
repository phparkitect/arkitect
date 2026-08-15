<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\Fqcn;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;

/**
 * Follows the `extends` chain only: an interface reached through
 * `implements` is something the class is, not something it extends — that
 * question belongs to IsA.
 */
final class Extend implements Constraint
{
    private readonly string $target;

    public function __construct(
        string $target,
        private readonly Depth $depth = Depth::Transitive,
    ) {
        $this->target = (new Fqcn($target))->toString();
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome
    {
        if (Depth::Direct === $this->depth) {
            return $this->declares($class)
                ? new Outcome()
                : $this->violation($class, \sprintf('does not directly extend %s', $this->target));
        }

        return match ($classGraph->hasAncestor($class->fqcn, $this->target)) {
            Membership::Yes => new Outcome(),
            Membership::No => $this->violation($class, \sprintf('does not extend %s', $this->target)),
            Membership::Unknown => Outcome::unresolved($class, \sprintf(
                'cannot be checked against %s: some ancestors were never parsed',
                $this->target
            )),
        };
    }

    private function declares(ParsedClass $class): bool
    {
        foreach ($class->extends as $reference) {
            if ($reference->name === $this->target) {
                return true;
            }
        }

        return false;
    }

    private function violation(ParsedClass $class, string $detail): Outcome
    {
        return new Outcome(new Violations(Violation::create($class, self::class, $detail)));
    }
}
