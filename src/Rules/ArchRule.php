<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;

class ArchRule implements DSL\ArchRule
{
    /** @var Specs */
    private $thats;

    /** @var Constraints */
    private $shoulds;

    /** @var string */
    private $because;

    /** @var array */
    private $classesToBeExcluded;

    public function __construct(Specs $specs, Constraints $constraints, string $because, array $classesToBeExcluded)
    {
        $this->thats = $specs;
        $this->shoulds = $constraints;
        $this->because = $because;
        $this->classesToBeExcluded = $classesToBeExcluded;
    }

    public function check(ClassDescription $classDescription, Violations $violations): void
    {
        $fqcn = FullyQualifiedClassName::fromString($classDescription->getFQCN());

        if (\in_array($fqcn->toString(), $this->classesToBeExcluded)) {
            return;
        }

        if (!$this->thats->allSpecsAreMatchedBy($classDescription)) {
            return;
        }

        $this->shoulds->checkAll($classDescription, $violations);
    }
}
