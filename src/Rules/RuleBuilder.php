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
