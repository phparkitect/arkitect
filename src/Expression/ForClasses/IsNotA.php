<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class IsNotA implements Expression
{
    /** @var class-string */
    private string $disallowedFqcn;

    /**
     * @param class-string $disallowedFqcn
     */
    public function __construct(string $disallowedFqcn)
    {
        $this->disallowedFqcn = $disallowedFqcn;
    }

    public function describe(ClassDescription $theClass, string $because = ''): Description
    {
        return new Description("{$theClass->getName()} should not be a $this->disallowedFqcn", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because = ''): void
    {
        if (is_a($theClass->getFQCN(), $this->disallowedFqcn, true)) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );

            $violations->add($violation);
        }
    }
}
