<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Rules\DSL\ArchRule;

class ClassSetRules
{
    private ClassSet $classSet;

    /** @var ArchRule[] */
    private array $rules;

    private function __construct(ClassSet $classSet, ArchRule ...$rules)
    {
        $this->classSet = $classSet;
        $this->rules = $rules;
    }

    public static function create(ClassSet $classSet, ArchRule ...$rules): self
    {
        return new self($classSet, ...$rules);
    }

    public function getClassSet(): ClassSet
    {
        return $this->classSet;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
