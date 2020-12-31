<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ClassSetRules;

class Config
{
    private array $classSetRules;

    public function __construct()
    {
        $this->classSetRules = [];
    }

    public function add(ClassSetRules $classSetRules): self
    {
        $this->classSetRules[] = $classSetRules;

        return $this;
    }

    public function getClassSetRules(): array
    {
        return $this->classSetRules;
    }
}
