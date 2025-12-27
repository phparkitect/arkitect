<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class HaveTrait implements Expression
{
    /** @var string */
    private $trait;

    public function __construct(string $trait)
    {
        $this->trait = $trait;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("should use the trait {$this->trait}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($theClass->hasTrait($this->trait)) {
            return;
        }

        $violations->add(
            Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            )
        );
    }
}
