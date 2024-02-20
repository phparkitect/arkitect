<?php

declare(strict_types=1);

namespace Arkitect\Expression\Boolean;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class Andx implements Expression
{
    /** @var Expression[] */
    private $expressions;

    public function __construct(Expression ...$expressions)
    {
        $this->expressions = $expressions;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $expressionsDescriptions = [];
        foreach ($this->expressions as $expression) {
            $expressionsDescriptions[] = $expression->describe($theClass, '')->toString();
        }

        return new Description(
            'all expressions must be true ('.implode(', ', $expressionsDescriptions).')',
            $because
        );
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        foreach ($this->expressions as $expression) {
            $newViolations = new Violations();
            $expression->evaluate($theClass, $newViolations, $because);
            if (0 !== $newViolations->count()) {
                $violations->add(Violation::create(
                    $theClass->getFQCN(),
                    ViolationMessage::withDescription(
                        $this->describe($theClass, $because),
                        "The class '".$theClass->getFQCN()."' violated the expression "
                        .$expression->describe($theClass, '')->toString()
                    )
                ));

                return;
            }
        }
    }
}
