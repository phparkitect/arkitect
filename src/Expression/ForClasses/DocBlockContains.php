<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class DocBlockContains implements Expression
{
    /** @var string */
    private $docBlock;

    public function __construct(string $docBlock)
    {
        $this->docBlock = $docBlock;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new PositiveDescription("should have a doc block that contains {$this->docBlock}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because, bool $stopOnFailure): void
    {
        if (!$theClass->containsDocBlock($this->docBlock)) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass, $because)->toString()
            );
            $violations->add($violation);
        }
    }
}
