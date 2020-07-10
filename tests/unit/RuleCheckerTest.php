<?php
declare(strict_types=1);

namespace ArkitectTests\unit;

use Arkitect\ArchViolationsException;
use Arkitect\ClassSet;
use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\RuleChecker;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\ViolationsStore;
use PHPUnit\Framework\TestCase;

class RuleCheckerTest extends TestCase
{
    public function test_qualcosa(): void
    {
        $classSet = $this->prophesize(ClassSet::class);

        $archRuleConstraint = $this->prophesize(ArchRuleConstraint::class);
        $archRuleConstraintToGivenClasses = $this->prophesize(ArchRuleGivenClasses::class);
        $archRuleConstraint
            ->get()
            ->willReturn($archRuleConstraintToGivenClasses)
            ->shouldBeCalled();

        $archRuleConstraintToGivenClasses->check($classSet)->shouldBeCalled();
        $archRuleConstraintToGivenClasses->getViolations()->willReturn(new ViolationsStore('Violation 2', 'Violation 3'))->shouldBeCalled();

        $archRuleGivenClasses = $this->prophesize(ArchRuleGivenClasses::class);
        $archRuleGivenClasses->check($classSet)->shouldBeCalled();
        $archRuleGivenClasses->getViolations()->willReturn(new ViolationsStore('Violation 1'))->shouldBeCalled();

        RuleChecker::checkThatClassesIn($classSet->reveal())
            ->meetTheFollowingRules($archRuleGivenClasses->reveal(), $archRuleConstraint->reveal());

        self::assertEquals(2,RuleChecker::assertionsCount());

        self::expectExceptionObject(new ArchViolationsException(
            new ViolationsStore('Violation 1', 'Violation 2', 'Violation 3')
        ));

        RuleChecker::run();
    }
}