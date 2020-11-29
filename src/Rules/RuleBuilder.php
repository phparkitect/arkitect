<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;
use Arkitect\Expression\Logical\Not;

class RuleBuilder
{
    private Specs $thats;

    private Constraints $shoulds;

    private string $because;

    public function __construct()
    {
        $this->thats = new Specs();
        $this->shoulds = new Constraints();
        $this->because = '';
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

    public function addShouldNot(Expression $should): self
    {
        $this->shoulds->add(new Not($should));

        return $this;
    }

    public function setBecause(string $because): self
    {
        $this->because = $because;

        return $this;
    }

    public function build(): ArchRule
    {
        return new ArchRule($this->thats, $this->shoulds, $this->because);
    }
}
