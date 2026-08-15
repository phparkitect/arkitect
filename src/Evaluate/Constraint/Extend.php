<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;

final class Extend implements Constraint
{
    public function __construct(
        private readonly string $target,
        private readonly Depth $depth = Depth::Transitive,
    ) {
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        $detail = Depth::Direct === $this->depth
            ? $this->checkDeclaration($class)
            : $this->checkChain($class, $classGraph);

        if (null === $detail) {
            return new Violations();
        }

        return new Violations([Violation::create($class, self::class, $detail)]);
    }

    private function checkDeclaration(ParsedClass $class): ?string
    {
        foreach ($class->extends as $parent) {
            if ($parent->name === $this->target) {
                return null;
            }
        }

        return \sprintf('does not directly extend %s', $this->target);
    }

    private function checkChain(ParsedClass $class, ClassGraph $classGraph): ?string
    {
        return match ($classGraph->hasAncestor($class->fqcn, $this->target)) {
            Membership::Yes => null,
            Membership::No => \sprintf('does not extend %s', $this->target),
            Membership::Unknown => \sprintf(
                'cannot be resolved against %s: some ancestors were never parsed',
                $this->target
            ),
        };
    }
}
