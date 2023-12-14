<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\MergeableExpression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class ResideInOneOfTheseNamespaces implements Expression, MergeableExpression
{
    /** @var string[] */
    private $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = array_unique($namespaces);
    }

    public function describe(ClassDescription $theClass, string $because = ''): Description
    {
        $descr = implode(', ', $this->namespaces);

        return new Description("resides in one of these namespaces: $descr", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because = ''): void
    {
        $resideInNamespace = false;
        foreach ($this->namespaces as $namespace) {
            if ($theClass->namespaceMatches($namespace.'*')) {
                $resideInNamespace = true;
            }
        }

        if (!$resideInNamespace) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because))
            );
            $violations->add($violation);
        }
    }

    public function mergeWith(Expression $expression): Expression
    {
        if (!$expression instanceof self) {
            throw new \InvalidArgumentException('Can not merge expressions. The given expression should be of type '.\get_class($this).' but is of type '.\get_class($expression));
        }

        return new self(...array_merge($this->namespaces, $expression->namespaces));
    }
}
