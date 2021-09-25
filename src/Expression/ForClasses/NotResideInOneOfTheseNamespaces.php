<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class NotResideInOneOfTheseNamespaces implements Expression
{
    /** @var string[] */
    private $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function describe(ClassDescription $theClass): Description
    {
        $descr = implode(', ', $this->namespaces);

        return new PositiveDescription("should not reside in one of these namespaces: $descr");
    }

    public function evaluate(ClassDescription $theClass, Violations $violations): void
    {
        $resideInNamespace = false;
        foreach ($this->namespaces as $namespace) {
            if ($theClass->namespaceMatches($namespace)) {
                $resideInNamespace = true;
            }
        }

        if ($resideInNamespace) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass)->toString()
            );
            $violations->add($violation);
        }
    }
}
