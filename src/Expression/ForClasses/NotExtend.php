<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\RuleException;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class NotExtend implements Expression
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
        return new PositiveDescription("should not extend {$this->className}");
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, RuleException $except): void
    {
        $extends = $theClass->getExtends();

        if (null === $extends) {
            return;
        }

        if ($extends->toString() !== $this->className || !$except->isAllowed($theClass->getFQCN())) {
            return;
        }

        $violation = Violation::create(
            $theClass->getFQCN(),
            $this->describe($theClass)->toString()
        );

        $violations->add($violation);
    }
}
