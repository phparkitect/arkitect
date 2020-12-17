<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ClassSet;
use Arkitect\Rules\DSL\ArchRule;

class Config
{
    private ?ClassSet $classSet;
    /**
     * @var ArchRule[]
     */
    private array $rules;

    public function checkThatClassesIn(ClassSet $classSet): self
    {
        $this->classSet = $classSet;

        return $this;
    }

    public function meetTheFollowingRules(ArchRule ...$rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function getRunner(): Runner
    {
        return new Runner($this->classSet, ...$this->rules);
    }
}
