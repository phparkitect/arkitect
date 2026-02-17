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

class Extend implements Expression
{
    /** @var array<string> */
    private array $classNames;

    public function __construct(string ...$classNames)
    {
        $this->classNames = $classNames;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $desc = implode(', ', $this->classNames);

        return new Description("should extend one of these classes: {$desc}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        try {
            $reflection = new \ReflectionClass($theClass->getFQCN());
            $parents = [];
            $parent = $reflection->getParentClass();
            while ($parent) {
                $parents[] = $parent->getName();
                $parent = $parent->getParentClass();
            }
        } catch (\ReflectionException $e) {
            return;
        }

        foreach ($this->classNames as $className) {
            foreach ($parents as $parentName) {
                if (FullyQualifiedClassName::fromString($parentName)->matches($className)) {
                    return;
                }
            }
        }

        $violations->add(Violation::create(
            $theClass->getFQCN(),
            ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
            $theClass->getFilePath()
        ));
    }
}
