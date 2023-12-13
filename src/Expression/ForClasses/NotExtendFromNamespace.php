<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NotExtendFromNamespace implements Expression
{
    /** @var string */
    private $namespace;

    /** @var string[] */
    private array $exceptions;

    public function __construct(string $namespace, array $exceptions = [])
    {
        $this->namespace = $namespace;
        $this->exceptions = $exceptions;
    }

    public function describe(ClassDescription $theClass, string $because = ''): Description
    {
        return new Description("should not extend from namespace {$this->namespace}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because = ''): void
    {
        $extends = $theClass->getExtends();

        if (null === $extends) {
            return;
        }

        if ($this->extendsAnExceptionNamespace($extends->toString())) {
            return;
        }

        if (!str_starts_with($extends->toString(), $this->namespace)) {
            return;
        }

        $violation = Violation::create(
            $theClass->getFQCN(),
            ViolationMessage::selfExplanatory($this->describe($theClass, $because))
        );

        $violations->add($violation);
    }

    private function extendsAnExceptionNamespace(string $extends): bool
    {
        foreach ($this->exceptions as $exceptionNamespace) {
            if (str_starts_with($extends, $exceptionNamespace)) {
                return true;
            }
        }

        return false;
    }
}
