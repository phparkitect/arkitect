<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\AbstractExpression;
use Arkitect\Expression\Description;
use Arkitect\Rules\Violations;

class ContainDocBlockLike extends AbstractExpression
{
    /** @var string */
    private $docBlock;

    public function __construct(string $docBlock)
    {
        $this->docBlock = $docBlock;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("should have a doc block that contains {$this->docBlock}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if (!$theClass->containsDocBlock($this->docBlock)) {
            $this->addViolation($theClass, $violations, $because);
        }
    }
}
