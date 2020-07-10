<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\ViolationsStore;

class RuleChecker
{
    /**
     * @var Assert[]
     */
    private static $assertions = [];

    /**
     * @var ClassSet
     */
    private $classSet;

    private function __construct()
    {
    }

    public static function checkThatClassesIn(ClassSet $classSet): self
    {
        $instance = new self();

        $instance->classSet = $classSet;

        return $instance;
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

        $instance = new self();

        $rules = array_map(function (ArchRuleGivenClasses $rule): Assert {
            return new Assert($this->classSet, $rule);
        }, $rules);

        self::$assertions = array_merge(self::$assertions, $rules);


        return $instance;
    }

    public static function run(): void
    {
        $violations = [];

        foreach (self::$assertions as $assert) {
            try {
                $assert->run();
            } catch (ArchViolationsException $exception) {
                $violations = array_merge($violations, $exception->violations()->toArray());
            }
        }

        if (!empty($violations)) {
            throw new ArchViolationsException(ViolationsStore::fromViolations(...$violations));
        }
    }

    public static function assertionsCount(): int
    {
        return count(self::$assertions);
    }
}
