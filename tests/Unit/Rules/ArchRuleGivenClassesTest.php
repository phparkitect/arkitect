<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\ClassSet;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\Violations;
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

    public function test_it_should_add_subscriber(): void
    {
        $classSet = $this->prophesize(ClassSet::class);
        $classSet->addSubscriber(Argument::any())->shouldBeCalled();
        $classSet->run()->shouldBeCalled();

        $this->archRuleGivenClass->check($classSet->reveal());
    }

    public function test_it_should_return_violation_store(): void
    {
        $this->assertInstanceOf(Violations::class, $this->archRuleGivenClass->getViolations());
    }

    public function test_it_should_return_self(): void
    {
        $this->assertEquals($this->archRuleGivenClass, $this->archRuleGivenClass);
    }
}
