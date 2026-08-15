<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Pattern;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\InternalClasses;

final class DependOnlyOnTheseNamespaces implements Constraint
{
    /** @var list<Pattern> */
    private readonly array $allowed;

    private readonly InternalClasses $internal;

    /** @param list<string> $namespaces */
    public function __construct(array $namespaces)
    {
        $this->allowed = array_map(static fn (string $n) => new Pattern($n), array_values($namespaces));
        $this->internal = new InternalClasses();
    }

    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        $violations = [];

        foreach ($class->dependencies as $dependency) {
            if ($this->isAllowed($class, $dependency->name)) {
                continue;
            }

            $violations[] = Violation::createAt(
                $class,
                $dependency,
                self::class,
                \sprintf('depends on %s', $dependency->name)
            );
        }

        return new Violations($violations);
    }

    private function isAllowed(ParsedClass $class, string $dependency): bool
    {
        if ($this->internal->contains($dependency)) {
            return true;
        }

        // a class can always reach its own namespace without having to
        // name it in every rule that constrains it
        $ownNamespace = $class->namespaceName();

        if ('' !== $ownNamespace && (new Pattern($ownNamespace))->matches($dependency)) {
            return true;
        }

        foreach ($this->allowed as $pattern) {
            if ($pattern->matches($dependency)) {
                return true;
            }
        }

        return false;
    }
}
