<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\NamespacePattern;
use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * Not the negation of DependOnlyOnTheseNamespaces: that one lists what is
 * permitted and forbids the rest, this one lists what is forbidden and
 * says nothing about the rest. Neither implies the other, so both exist.
 */
final class NotDependOnTheseNamespaces implements Constraint
{
    /** @var list<NamespacePattern> */
    private readonly array $forbidden;

    /** @param list<string> $namespaces */
    public function __construct(array $namespaces)
    {
        $this->forbidden = array_map(static fn (string $n) => new NamespacePattern($n), array_values($namespaces));
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome
    {
        $violations = [];

        foreach ($class->dependencies as $dependency) {
            if (!$this->isForbidden($dependency->name)) {
                continue;
            }

            $violations[] = Violation::createAt(
                $class,
                $dependency,
                self::class,
                \sprintf('depends on %s', $dependency->name)
            );
        }

        return new Outcome(new Violations(...$violations));
    }

    private function isForbidden(string $dependency): bool
    {
        foreach ($this->forbidden as $pattern) {
            if ($pattern->contains($dependency)) {
                return true;
            }
        }

        return false;
    }
}
