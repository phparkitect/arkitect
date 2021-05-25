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

    public function __construct()
    {
        $this->classSetRules = [];
    }

    public function add(ClassSet $classSet, ArchRule ...$rules): self
    {
        $this->classSetRules[] = ClassSetRules::create($classSet, ...$rules);

        return $this;
    }

    public function getClassSetRules(): array
    {
        return $this->classSetRules;
    }
}
