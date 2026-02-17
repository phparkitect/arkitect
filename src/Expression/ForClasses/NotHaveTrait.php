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

        $reflection = new \ReflectionClass($theClass->getFQCN());
        $allTraits = [];
        $class = $reflection;
        while ($class) {
            foreach ($class->getTraitNames() as $traitName) {
                $allTraits[] = $traitName;
            }
            $class = $class->getParentClass() ?: null;
        }

        $found = array_reduce(
            $allTraits,
            static fn (bool $carry, string $traitName): bool => $carry || FullyQualifiedClassName::fromString($traitName)->matches($trait),
            false
        );

        if ($found) {
            $violations->add(
                Violation::create(
                    $theClass->getFQCN(),
                    ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                    $theClass->getFilePath()
                )
            );
        }
    }
}
