<?php

declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

abstract class AbstractExpression implements Expression
{
    public function appliesTo(ClassDescription $theClass): bool
    {
        return true;
    }

    protected function addViolation(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $violations->add(
            Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            )
        );
    }
}
