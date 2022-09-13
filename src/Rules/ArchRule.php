<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;

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

    /** @var bool */
    private $runOnlyThis;

    public function __construct(
        Specs $specs,
        Constraints $constraints,
        string $because,
        array $classesToBeExcluded,
        bool $runOnlyThis
    ) {
        $this->thats = $specs;
        $this->shoulds = $constraints;
        $this->because = $because;
        $this->classesToBeExcluded = $classesToBeExcluded;
        $this->runOnlyThis = $runOnlyThis;
    }

    public function check(ClassDescription $classDescription, Violations $violations): void
    {
        if ($classDescription->namespaceMatchesOneOfTheseNamespaces($this->classesToBeExcluded)) {
            return;
        }

        if (!$this->thats->allSpecsAreMatchedBy($classDescription, $this->because)) {
            return;
        }

        $this->shoulds->checkAll($classDescription, $violations, $this->because);
    }

    public function isRunOnlyThis(): bool
    {
        return $this->runOnlyThis;
    }

    public function runOnlyThis(): DSL\ArchRule
    {
        $this->runOnlyThis = true;

        return $this;
    }
}
