<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;

class ResideInOneOfTheseNamespaces implements Expression
{
    private array $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function describe(ClassDescription $theClass): Description
    {
        $descr = implode(', ', $this->namespaces);

        return new PositiveDescription("{$theClass->getFQCN()} [resides|doesn't reside] in one of these namespaces: $descr");
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        foreach ($this->namespaces as $namespace) {
            if ($theClass->namespaceMatches($namespace)) {
                return true;
            }
        }

        return false;
    }
}
