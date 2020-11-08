<?php

declare(strict_types=1);

namespace ArkitectTests\unit\Specs;

use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Constraints\ConstraintsStore;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Specs\ArchRuleSpec;
use Arkitect\Specs\DependOnClassSpec;
use Arkitect\Specs\DependOnNamespaceSpec;
use Arkitect\Specs\DoNotDependOnClassSpec;
use Arkitect\Specs\DoNotHaveNameMatchingSpec;
use Arkitect\Specs\DoNotImplementInterfaceSpec;
use Arkitect\Specs\HaveNameMatchingSpec;
use Arkitect\Specs\ImplementInterfaceSpec;
use Arkitect\Specs\ResideInNamespaceSpec;
use Arkitect\Specs\SpecsStore;
use PHPUnit\Framework\TestCase;

class ArchRuleSpecTest extends TestCase
{
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $archRuleGivenClasses;
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $specsStore;
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $constraintStore;
    /**
     * @var ArchRuleSpec
     */
    private $archRuleSpec;

    public function setUp(): void
    {
        $this->archRuleGivenClasses = $this->prophesize(ArchRuleGivenClasses::class);
        $this->specsStore = $this->prophesize(SpecsStore::class);
        $this->constraintStore = $this->prophesize(ConstraintsStore::class);

        $this->archRuleSpec = new ArchRuleSpec(
            $this->archRuleGivenClasses->reveal(),
            $this->specsStore->reveal(),
            $this->constraintStore->reveal()
        );
    }

    public function test_it_should_return_arch_rule_constraint_when_should_function_called(): void
    {
        $archRuleConstraint = new ArchRuleConstraint(
            $this->archRuleGivenClasses->reveal(),
            $this->constraintStore->reveal()
        );

        $this->assertEquals($archRuleConstraint, $this->archRuleSpec->should());
    }

    public function test_it_should_add_reside_in_namespace(): void
    {
        $namespace = 'namespace';
        $this->specsStore->add(new ResideInNamespaceSpec($namespace))->shouldBeCalled();

        $this->archRuleSpec->resideInNamespace($namespace);
    }

    public function test_it_should_add_have_name_matching(): void
    {
        $name = 'name';
        $this->specsStore->add(new HaveNameMatchingSpec($name))->shouldBeCalled();

        $this->archRuleSpec->haveNameMatching($name);
    }

    public function test_it_should_add_do_not_have_name_matching(): void
    {
        $name = 'name';
        $this->specsStore->add(new DoNotHaveNameMatchingSpec($name))->shouldBeCalled();

        $this->archRuleSpec->doNotHaveNameMatching($name);
    }

    public function test_it_should_add_implement_interface(): void
    {
        $interface = 'interface';
        $this->specsStore->add(new ImplementInterfaceSpec($interface))->shouldBeCalled();

        $this->archRuleSpec->implementInterface($interface);
    }

    public function test_it_should_add_do_not_implement_interface(): void
    {
        $interface = 'interface';
        $this->specsStore->add(new DoNotImplementInterfaceSpec($interface))->shouldBeCalled();

        $this->archRuleSpec->doNotImplementInterface($interface);
    }

    public function test_it_should_add_depend_on_class(): void
    {
        $class = 'class';
        $this->specsStore->add(new DependOnClassSpec($class))->shouldBeCalled();

        $this->archRuleSpec->dependOnClass($class);
    }

    public function test_it_should_add_do_not_depend_on_class(): void
    {
        $class = 'class';
        $this->specsStore->add(new DoNotDependOnClassSpec($class))->shouldBeCalled();

        $this->archRuleSpec->doNotDependOnClass($class);
    }

    public function test_it_should_add_depend_on_namespace(): void
    {
        $namespace = 'namespace';
        $this->specsStore->add(new DependOnNamespaceSpec($namespace))->shouldBeCalled();

        $this->archRuleSpec->dependOnNamespace($namespace);
    }

    public function test_it_should_add_do_not_depend_on_namespace(): void
    {
        $namespace = 'namespace';
        $this->specsStore->add(new DoNotDependOnClassSpec($namespace))->shouldBeCalled();

        $this->archRuleSpec->doNotDependOnClass($namespace);
    }

    public function test_it_should_return_arch_rule_given_classes_when_get_is_called(): void
    {
        $this->assertInstanceOf(ArchRuleGivenClasses::class, $this->archRuleSpec->get());
    }
}
