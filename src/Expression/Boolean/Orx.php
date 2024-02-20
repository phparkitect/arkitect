<?php
declare(strict_types=1);

namespace Arkitect\Expression\Boolean;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class Orx implements Expression
{
    /** @var Expression[] */
    private $expressions;

    public function __construct(array $expressions)
    {
        if (\count($expressions) <= 1) {
            throw new \InvalidArgumentException('at least two expression are required');
        }
        $this->expressions = $expressions;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description('at least one expression must be true', $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        foreach ($this->expressions as $expression) {
            $newViolations = new Violations();
            $expression->evaluate($theClass, $newViolations, $because);
            if (0 === $newViolations->count()) {
                return;
            }
        }

        $violations->add(Violation::create(
            $theClass->getFQCN(),
            ViolationMessage::selfExplanatory($this->describe($theClass, $because))
        ));
    }
}
