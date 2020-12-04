<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ClassSet;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\RuleChecker;
use Arkitect\Rules\Violations;

class Runner
{
    private ClassSet $classSet;
    /**
     * @var ArchRule[]
     */
    private array $rules;

    private RuleChecker $ruleChecker;

    public function __construct(ClassSet $classSet, ArchRule ...$rules)
    {
        $this->classSet = $classSet;
        $this->rules = $rules;
        $this->ruleChecker = new RuleChecker($this->classSet, new Violations());
    }

    public function run(): Violations
    {
        foreach ($this->rules as $rule) {
            $this->ruleChecker->check($rule);
        }

        return $this->ruleChecker->getViolations();
    }
}
