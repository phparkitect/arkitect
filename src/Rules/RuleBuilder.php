<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;

class RuleBuilder
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

    public function __construct()
    {
        $this->thats = new Specs();
        $this->shoulds = new Constraints();
        $this->because = '';
        $this->classesToBeExcluded = [];
        $this->runOnlyThis = false;
    }

    public function addThat(Expression $that): self
    {
        $this->thats->add($that);

        return $this;
    }

    public function addShould(Expression $should): self
    {
        $this->shoulds->add($should);

        return $this;
    }

    public function setBecause(string $because): self
    {
        $this->because = $because;

        return $this;
    }

    public function build(): ArchRule
    {
        return new ArchRule(
            $this->thats,
            $this->shoulds,
            $this->because,
            $this->classesToBeExcluded,
            $this->runOnlyThis
        );
    }

    public function classesToBeExcluded(string ...$classesToBeExcluded): self
    {
        $this->classesToBeExcluded = $classesToBeExcluded;

        return $this;
    }

    public function setRunOnlyThis(): self
    {
        $this->runOnlyThis = true;

        return $this;
    }
}
