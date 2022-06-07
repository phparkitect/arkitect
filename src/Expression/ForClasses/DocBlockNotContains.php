<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class DocBlockNotContains implements Expression
{
    /** @var string */
    private $docBlock;

    public function __construct(string $docBlock)
    {
        $this->docBlock = $docBlock;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new PositiveDescription("should not have a doc block that contains {$this->docBlock}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($theClass->containsDocBlock($this->docBlock)) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass, $because)->toString()
            );
            $violations->add($violation);
        }
    }
}
