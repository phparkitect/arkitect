<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\Violations;

class RuleChecker
{
    /**
     * @var Assert[]
     */
    private $assertions = [];

    /**
     * @var ClassSet
     */
    private $classSet;

    public function __construct()
    {
    }

    public function checkThatClassesIn(ClassSet $classSet): self
    {
        $this->classSet = $classSet;

        return $this;
    }

    /**
     * @param ArchRuleGivenClasses|ArchRuleConstraint ...$rules
     */
    public function meetTheFollowingRules(...$rules): self
    {
        $rules = array_map(function ($rule): ArchRuleGivenClasses {
            switch (true) {
                case $rule instanceof ArchRuleGivenClasses: return $rule;
                case $rule instanceof ArchRuleConstraint: return $rule->get();
                default: throw new \RuntimeException('Unknown rule class: ' . get_class($rule));
            }
        }, $rules);

        $rules = array_map(function (ArchRuleGivenClasses $rule): Assert {
            return new Assert($this->classSet, $rule);
        }, $rules);

        $this->assertions = array_merge($this->assertions, $rules);

        return $this;
    }

    public function run(): Violations
    {
        $violations = [];

        foreach ($this->assertions as $assert) {
            try {
                $assert->run();
            } catch (ArchViolationsException $exception) {
                $violations = array_merge($violations, $exception->violations()->toArray());
            }
        }

        return Violations::fromViolations(...$violations);
    }

    public function assertionsCount(): int
    {
        return count($this->assertions);
    }
}
