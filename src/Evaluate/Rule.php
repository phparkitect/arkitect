<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Selector\Selection;
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
        $unresolved = [];

        foreach ($classes as $class) {
            $selection = $this->select($class, $classGraph);

            if (Selection::Unresolved === $selection) {
                // not checked and not skipped: we couldn't tell whether this
                // rule is even about it, and either guess would be silent
                $unresolved[] = UnresolvedClass::create(
                    $class,
                    'cannot be matched against this rule: some ancestors were never parsed'
                );

                continue;
            }

            if (Selection::No === $selection) {
                continue;
            }

            ++$checked;

            foreach ($this->constraints as $constraint) {
                $outcome = $constraint->evaluate($class, $classGraph);

                foreach ($outcome->violations as $violation) {
                    $violations[] = $violation;
                }

                foreach ($outcome->unresolved as $unresolvedClass) {
                    $unresolved[] = $unresolvedClass;
                }
            }
        }

        return new RuleResult($checked, new Violations($violations), new UnresolvedClasses($unresolved));
    }

    /**
     * Selectors are combined with and, so one No settles it — even if another
     * selector couldn't decide, since the rule is not about this class either
     * way. Unresolved only survives when nothing else ruled the class out.
     */
    private function select(ParsedClass $class, ClassGraph $classGraph): Selection
    {
        $unresolved = false;

        foreach ($this->selectors as $selector) {
            $selection = $selector->matches($class, $classGraph);

            if (Selection::No === $selection) {
                return Selection::No;
            }

            if (Selection::Unresolved === $selection) {
                $unresolved = true;
            }
        }

        return $unresolved ? Selection::Unresolved : Selection::Yes;
    }
}
