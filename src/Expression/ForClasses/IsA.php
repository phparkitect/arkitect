<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class IsA implements Expression
{
    /** @var class-string */
    private string $allowedFqcn;

    /**
     * @param class-string $allowedFqcn
     */
    public function __construct(string $allowedFqcn)
    {
        $this->allowedFqcn = $allowedFqcn;
    }

    public function describe(ClassDescription $theClass, string $because = ''): Description
    {
        return new Description("should inherit from: $this->allowedFqcn", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because = ''): void
    {
        if (!is_a($theClass->getFQCN(), $this->allowedFqcn, true)) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );

            $violations->add($violation);
        }
    }
}
