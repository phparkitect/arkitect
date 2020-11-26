<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Rules\DSL\ArchRule;
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

    public function meetTheFollowingRules(ArchRuleGivenClasses ...$rules): self
    {
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
        return \count($this->assertions);
    }
}
