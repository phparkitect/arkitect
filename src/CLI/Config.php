<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\Rules\DSL\ArchRule;

class Config
{
    /** @var array */
    private $classSetRules;
    /** @var bool */
    private $runOnlyARule;

    public function __construct()
    {
        $this->classSetRules = [];
        $this->runOnlyARule = false;
    }

    public function add(ClassSet $classSet, ArchRule ...$rules): self
    {
        if ($this->runOnlyARule) {
            return $this;
        }

        /** @var ArchRule $rule */
        foreach ($rules as $rule) {
            if ($rule->isRunOnlyThis()) {
                $rules = [];
                $rules[] = $rule;

                $this->runOnlyARule = true;
                break;
            }
        }

        $this->classSetRules[] = ClassSetRules::create($classSet, ...$rules);

        return $this;
    }

    public function getClassSetRules(): array
    {
        return $this->classSetRules;
    }
}
