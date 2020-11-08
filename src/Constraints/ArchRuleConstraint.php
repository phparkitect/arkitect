<?php
declare(strict_types=1);

namespace Arkitect\Constraints;

use Arkitect\Rules\ArchRuleGivenClasses;

class ArchRuleConstraint
{
    /**
     * @var ConstraintsStore
     */
    private $constraintsStore;

    /**
     * @var ArchRuleGivenClasses
     */
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

    public function implement(string $interface): self
    {
        $this->constraintsStore->add(new ImplementConstraint($interface));

        return $this;
    }

    public function notHaveDependencyOutsideNamespace(string $namespace): self
    {
        $this->constraintsStore->add(new NotHaveDependencyOutsideNamespace($namespace));

        return $this;
    }

    public function dependOnClassesInNamespace($namespace): self
    {
        $this->constraintsStore->add(new DependsOnClassesInNamespaceConstraint($namespace));

        return $this;
    }

    public function haveNameMatching(string $name): self
    {
        $this->constraintsStore->add(new HaveNameMatching($name));

        return $this;
    }
}
