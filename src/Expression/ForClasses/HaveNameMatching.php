<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class HaveNameMatching implements Expression
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function describe(ClassDescription $theClass): string
    {
        return "{$theClass->getFQCN()} has a name that match {$this->name}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->nameMatches($this->name);
    }
}
