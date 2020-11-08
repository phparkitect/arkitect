<?php
declare(strict_types=1);

namespace Arkitect\Specs;

use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Constraints\ConstraintsStore;
use Arkitect\Rules\ArchRuleGivenClasses;

class ArchRuleSpec
{
    private $specsStore;

    private $constraintsStore;

    private $parent;

    public function __construct(ArchRuleGivenClasses $parent, SpecsStore $specStore, ConstraintsStore $constraintsStore)
    {
        $this->parent = $parent;
        $this->specsStore = $specStore;
        $this->constraintsStore = $constraintsStore;
    }

    public function should(): ArchRuleConstraint
    {
        return new ArchRuleConstraint($this->parent, $this->constraintsStore);
    }

    public function resideInNamespace(string $namespace): self
    {
        $this->specsStore->add(new ResideInNamespaceSpec($namespace));

        return $this;
    }

    public function doNotResideInNamespace(string $namespace): self
    {
        $this->specsStore->add(new DoNotResideInNamespaceSpec($namespace));

        return $this;
    }

    public function haveNameMatching(string $name): self
    {
        $this->specsStore->add(new HaveNameMatchingSpec($name));

        return $this;
    }

    public function doNotHaveNameMatching(string $name): self
    {
        $this->specsStore->add(new DoNotHaveNameMatchingSpec($name));

        return $this;
    }

    public function implementInterface(string $interface): self
    {
        $this->specsStore->add(new ImplementInterfaceSpec($interface));

        return $this;
    }

    public function doNotImplementInterface(string $interface): self
    {
        $this->specsStore->add(new DoNotImplementInterfaceSpec($interface));

        return $this;
    }

    public function dependOnClass(string $class): self
    {
        $this->specsStore->add(new DependOnClassSpec($class));

        return $this;
    }

    public function doNotDependOnClass(string $class): self
    {
        $this->specsStore->add(new DoNotDependOnClassSpec($class));

        return $this;
    }

    public function dependOnNamespace(string $namespace): self
    {
        $this->specsStore->add(new DependOnNamespaceSpec($namespace));

        return $this;
    }

    public function doNotDependOnNamespace(string $namespace): self
    {
        $this->specsStore->add(new DoNotDependOnNamespaceSpec($namespace));

        return $this;
    }

    public function get(): ArchRuleGivenClasses
    {
        return $this->parent;
    }
}
