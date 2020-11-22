<?php
declare(strict_types=1);

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

abstract class BaseSpec
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    abstract public function apply(ClassDescription $theClass);
}
