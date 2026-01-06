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
        // Check if the class extends the required class (using parsed AST info)
        $extends = $theClass->getExtends();
        foreach ($extends as $extend) {
            if ($extend->matches($this->allowedFqcn)) {
                return;
            }
        }

        // Check if the class implements the required interface (using parsed AST info)
        $interfaces = $theClass->getInterfaces();
        foreach ($interfaces as $interface) {
            if ($interface->matches($this->allowedFqcn)) {
                return;
            }
        }

        // No match found - create violation
        $violation = Violation::create(
            $theClass->getFQCN(),
            ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
            $theClass->getFilePath()
        );

        $violations->add($violation);
    }
}
