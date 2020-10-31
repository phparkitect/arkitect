<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\Violations;
use Arkitect\Validation\Engine;
use Arkitect\Validation\Rule;

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

    public function meetTheFollowingRules(Rule ...$rules): self
    {
        $engine = new Engine();
        $engine->addRules($rules);

        // T

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
