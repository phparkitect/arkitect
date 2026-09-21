<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Pattern;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * NotDependOnTheseNamespaces reads its arguments as namespaces, so a class
 * that must not be reached is named here instead.
 */
final class NotDependOnTheseClasses implements Constraint
{
    /** @var list<Pattern> */
    private readonly array $forbidden;

    /** @param list<string> $classes */
    public function __construct(array $classes)
    {
        $this->forbidden = array_map(static fn (string $c) => new Pattern($c), array_values($classes));
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
            if ($pattern->matches($dependency)) {
                return true;
            }
        }

        return false;
    }
}
