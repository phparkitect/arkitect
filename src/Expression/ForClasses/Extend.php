<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class Extend implements Expression
{
    /**
     * @var string
     */
    private $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function describe(ClassDescription $theClass): Description
    {
        return new PositiveDescription("should extend {$this->className}");
    }

    public function evaluate(ClassDescription $theClass, Violations $violations): void
    {
        $extends = $theClass->getExtends();

        if (null === $extends) {
            return;
        }

        if ($extends->toString() === $this->className) {
            return;
        }

        $violation = Violation::create(
            $theClass->getFQCN(),
            $this->describe($theClass)->toString()
        );

        $violations->add($violation);
    }
}
