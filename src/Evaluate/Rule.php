<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Selector\Selector;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * Selectors decide which classes the rule is about; constraints decide what
 * those classes must satisfy. With no selectors the rule is about every
 * class it is given.
 */
final class Rule
{
    /**
     * @param list<Selector>   $selectors
     * @param list<Constraint> $constraints
     */
    public function __construct(
        private readonly array $selectors,
        private readonly array $constraints,
    ) {
    }

    /** @param list<ParsedClass> $classes */
    public function check(array $classes, ClassGraph $classGraph): RuleResult
    {
        $checked = 0;
        $violations = [];

        foreach ($classes as $class) {
            if (!$this->selects($class, $classGraph)) {
                continue;
            }

            ++$checked;

            foreach ($this->constraints as $constraint) {
                foreach ($constraint->evaluate($class, $classGraph) as $violation) {
                    $violations[] = $violation;
                }
            }
        }

        return new RuleResult($checked, new Violations($violations));
    }

    private function selects(ParsedClass $class, ClassGraph $classGraph): bool
    {
        foreach ($this->selectors as $selector) {
            if (!$selector->matches($class, $classGraph)) {
                return false;
            }
        }

        return true;
    }
}
