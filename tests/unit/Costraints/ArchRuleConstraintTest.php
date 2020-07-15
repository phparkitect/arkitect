<?php
declare(strict_types=1);

namespace ArkitectTests\unit\Costraints;

use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Constraints\ConstraintsStore;
use Arkitect\Constraints\DependsOnClassesInNamespaceConstraint;
use Arkitect\Constraints\HaveNameMatching;
use Arkitect\Constraints\ImplementConstraint;
use Arkitect\Constraints\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\ArchRuleGivenClasses;
use PHPUnit\Framework\TestCase;

class ArchRuleConstraintTest extends TestCase
{
    /**
     * @var ArchRuleGivenClasses
     */
    private $archRuleGivenClass;
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $constraintStore;

    public function setUp(): void
    {
        $this->archRuleGivenClass = new ArchRuleGivenClasses();
        $this->constraintStore = $this->prophesize(ConstraintsStore::class);
    }

    public function test_it_should_return_archrulegivenclass_when_call_get(): void
    {
        $archRule = new ArchRuleConstraint($this->archRuleGivenClass, $this->constraintStore->reveal());

        $this->assertEquals($this->archRuleGivenClass, $archRule->get());
    }

    public function test_it_should_add_to_constraint_store_when_implement_is_called(): void
    {
        $interface = 'foo';
        $this->constraintStore->add(new ImplementConstraint($interface))->shouldBeCalled();
        $archRule = new ArchRuleConstraint($this->archRuleGivenClass, $this->constraintStore->reveal());
        $archRule->implement($interface);
    }

    public function test_it_should_add_to_constraint_store_when_not_have_dependency_outside_namespace_is_called(): void
    {
        $namespace = 'foo';
        $this->constraintStore->add(new NotHaveDependencyOutsideNamespace($namespace))->shouldBeCalled();
        $archRule = new ArchRuleConstraint($this->archRuleGivenClass, $this->constraintStore->reveal());
        $archRule->notHaveDependencyOutsideNamespace($namespace);
    }

    public function test_it_should_add_to_constraint_store_when_depend_on_classes_in_namespace_is_called(): void
    {
        $namespace = 'foo';
        $this->constraintStore->add(new DependsOnClassesInNamespaceConstraint($namespace))->shouldBeCalled();
        $archRule = new ArchRuleConstraint($this->archRuleGivenClass, $this->constraintStore->reveal());
        $archRule->dependOnClassesInNamespace($namespace);
    }

    public function test_it_should_add_to_constraint_store_when_have_name_matching_is_called(): void
    {
        $name = 'foo';
        $this->constraintStore->add(new HaveNameMatching($name))->shouldBeCalled();
        $archRule = new ArchRuleConstraint($this->archRuleGivenClass, $this->constraintStore->reveal());
        $archRule->haveNameMatching($name);
    }
}
