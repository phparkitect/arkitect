<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class NotExtend implements Expression
{
    /** @var string */
    private $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new PositiveDescription("should not extend {$this->className}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $extends = $theClass->getExtends();

        if (null === $extends) {
            return;
        }

        if ($extends->toString() !== $this->className) {
            return;
        }

        $violation = Violation::create(
            $theClass->getFQCN(),
            $this->describe($theClass, $because)->toString()
        );

        $violations->add($violation);
    }
}
