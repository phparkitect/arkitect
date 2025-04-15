<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NegateDecorator implements Expression
{
    /** @var Expression */
    private $expression;

    public function __construct(Expression $expression)
    {
        $this->expression = $expression;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $description = $this->expression->describe($theClass, $because)->toString();

        $description = str_replace(
            ['should not'],
            ['should'],
            $description,
            $count
        );

        if (0 === $count) {
            $description = str_replace(
                ['should'],
                ['should not'],
                $description,
                $count
            );
        }

        return new Description($description, '');
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $this->expression->evaluate($theClass, $currentViolations = new Violations(), $because);

        if (0 === $currentViolations->count()) {
            $violations->add(
                Violation::create(
                    $theClass->getFQCN(),
                    ViolationMessage::selfExplanatory($this->describe($theClass, $because))
                )
            );
        }
    }
}
