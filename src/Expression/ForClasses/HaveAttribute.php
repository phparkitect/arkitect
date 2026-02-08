<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\AbstractExpression;
use Arkitect\Expression\Description;
use Arkitect\Rules\Violations;

final class HaveAttribute extends AbstractExpression
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
        if (!$theClass->hasAttribute($this->attribute)) {
            $this->addViolation($theClass, $violations, $because);
        }
    }
}
