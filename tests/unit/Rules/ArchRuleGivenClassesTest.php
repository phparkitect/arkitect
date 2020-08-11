<?php

declare(strict_types=1);


namespace ArkitectTests\unit\Rules;

use Arkitect\ClassSet;
use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\ViolationsStore;
use Arkitect\Specs\ArchRuleSpec;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ArchRuleGivenClassesTest extends TestCase
{
    /**
     * @var ArchRuleGivenClasses
     */
    private $archRuleGivenClass;

    public function setUp(): void
    {
        $this->archRuleGivenClass = new ArchRuleGivenClasses();
    }

    public function test_it_should_return_arch_rule_spec_when_that_is_called(): void
    {
        $this->assertInstanceOf(ArchRuleSpec::class, $this->archRuleGivenClass->that());
    }

    public function test_it_should_return_arch_rule_constraint_when_that_is_should(): void
    {
        $this->assertInstanceOf(ArchRuleConstraint::class, $this->archRuleGivenClass->should());
    }

    public function test_it_should_add_subscriber(): void
    {
        $classSet = $this->prophesize(ClassSet::class);
        $classSet->addSubscriber(Argument::any())->shouldBeCalled();
        $classSet->excludeFiles([])->shouldBeCalled();
        $classSet->run()->shouldBeCalled();

        $this->archRuleGivenClass->check($classSet->reveal());
    }

    public function test_it_should_return_violation_store(): void
    {
        $this->assertInstanceOf(ViolationsStore::class, $this->archRuleGivenClass->getViolations());
    }

    public function test_it_should_return_self(): void
    {
        $this->assertEquals($this->archRuleGivenClass, $this->archRuleGivenClass->get());
    }
}
