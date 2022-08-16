<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class HaveAttribute implements Expression
{
    /** @var string */
    private $attribute;

    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("should have the attribute {$this->attribute}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($theClass->hasAttribute($this->attribute)) {
            return;
        }

        $violations->add(
            Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because))
            )
        );
    }
}
