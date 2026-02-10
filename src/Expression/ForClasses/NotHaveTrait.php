<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NotHaveTrait implements Expression
{
    /** @var string */
    private $trait;

    public function __construct(string $trait)
    {
        $this->trait = $trait;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("should not use the trait {$this->trait}", $because);
    }

    public function appliesTo(ClassDescription $theClass): bool
    {
        return !$theClass->isInterface();
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($theClass->isInterface()) {
            return;
        }

        $trait = $this->trait;
        $traits = $theClass->getTraits();
        $usesTrait = static fn (FullyQualifiedClassName $FQCN): bool => $FQCN->matches($trait);

        if (\count(array_filter($traits, $usesTrait)) > 0) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );
            $violations->add($violation);
        }
    }
}
