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

    public function __construct(Expression ...$expressions)
    {
        $this->expressions = $expressions;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $expressionsDescriptions = [];
        foreach ($this->expressions as $expression) {
            $expressionsDescriptions[] = $expression->describe($theClass, $because)->toString();
        }
        $expressionsDescriptionsString = implode("\nOR\n", array_unique($expressionsDescriptions))."\n";

        return new Description($expressionsDescriptionsString, $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if (0 === \count($this->expressions)) {
            return;
        }

        foreach ($this->expressions as $expression) {
            $newViolations = new Violations();
            $expression->evaluate($theClass, $newViolations, '');
            if (0 === $newViolations->count()) {
                return;
            }
        }

        $violations->add(
            Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::withDescription($this->describe($theClass, $because), 'All OR expressions failed: ')
            )
        );
    }
}
