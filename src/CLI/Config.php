<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\Rules\DSL\ArchRule;

class Config
{
    private array $classSetRules;
    private bool $runOnlyARule;
    private bool $parseCustomAnnotations;

    public function __construct()
    {
        $this->classSetRules = [];
        $this->runOnlyARule = false;
        $this->parseCustomAnnotations = true;
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

    public function skipParsingCustomAnnotations(): self
    {
        $this->parseCustomAnnotations = false;

        return $this;
    }

    public function isParseCustomAnnotationsEnabled(): bool
    {
        return $this->parseCustomAnnotations;
    }
}
