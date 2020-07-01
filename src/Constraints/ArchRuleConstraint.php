<?php

namespace Arkitect\Constraints;

use Arkitect\Rules\ArchRuleGivenClasses;

class ArchRuleConstraint
{
    private $constraintsStore;

    private $parent;

    public function __construct(ArchRuleGivenClasses $parent, ConstraintsStore $constraintsStore)
    {
        $this->constraintsStore = $constraintsStore;
        $this->parent = $parent;
    }

    public function get(): ArchRuleGivenClasses
    {
        return $this->parent;
    }

    public function implement(string $interface): ArchRuleConstraint
    {
        $this->constraintsStore->add(new ImplementConstraint($interface));

        return $this;
    }

    public function notHaveDependencyOutsideNamespace(string $namespace): ArchRuleConstraint
    {
        $this->constraintsStore->add(new NotHaveDependencyOutsideNamespace($namespace));

        return $this;
    }

    public function dependOnClassesInNamespace($namespace): ArchRuleConstraint
    {
        $this->constraintsStore->add(new DependsOnClassesInNamespaceConstraint($namespace));

        return $this;
    }

    public function haveNameMatching(string $name): ArchRuleConstraint
    {
        $this->constraintsStore->add(new HaveNameMatching($name));

        return $this;
    }

}