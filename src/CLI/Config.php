<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;

class Config
{
    /** @var array */
    private $classSetRules;

    public function __construct()
    {
        $this->classSetRules = [];
    }

    public function add(ClassSet $classSet, array $rules): self
    {
        $this->classSetRules[] = ClassSetRules::create($classSet, $rules);

        return $this;
    }

    public function getClassSetRules(?string $ruleFilter = null): array
    {
        if (null !== $ruleFilter) {
            return $this->filterRuleIntoClassSetRules($ruleFilter);
        }

        return $this->classSetRules;
    }

    private function filterRuleIntoClassSetRules(string $ruleFilter): array
    {
        /** @var ClassSetRules $classSetRules */
        foreach ($this->classSetRules as $index => $classSetRules) {
            $rule = $classSetRules->getRulesByName($ruleFilter);
            if (null === $rule) {
                unset($this->classSetRules[$index]);
                continue;
            }

            $ruleFiltered = [];
            $ruleFiltered[$ruleFilter] = $rule;
            $this->classSetRules[$index] = ClassSetRules::create($classSetRules->getClassSet(), $ruleFiltered);
        }

        return $this->classSetRules;
    }
}
