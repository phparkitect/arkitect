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
    /** @var array<class-string> */
    private array $allowedFqcnList;

    /**
     * @param class-string ...$allowedFqcnList
     */
    public function __construct(string ...$allowedFqcnList)
    {
        $this->allowedFqcnList = $allowedFqcnList;
    }

    public function describe(ClassDescription $theClass, string $because = ''): Description
    {
        $allowedFqcnList = implode(', ', $this->allowedFqcnList);

        return new Description("should inherit from one of: $allowedFqcnList", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because = ''): void
    {
        if (!$this->isA($theClass, ...$this->allowedFqcnList)) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );

            $violations->add($violation);
        }
    }

    /**
     * @param class-string ...$allowedFqcnList
     */
    private function isA(ClassDescription $theClass, string ...$allowedFqcnList): bool
    {
        foreach ($allowedFqcnList as $allowedFqcn) {
            if (is_a($theClass->getFQCN(), $allowedFqcn, true)) {
                return true;
            }
        }

        return false;
    }
}
