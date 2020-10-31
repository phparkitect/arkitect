<?php
declare(strict_types=1);

namespace ArkitectTests\unit;

use Arkitect\ArchViolationsException;
use Arkitect\ClassSet;
use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\RuleChecker;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class RuleCheckerTest extends TestCase
{
    public function test_it_returns_violations(): void
    {
        $classSet = $this->prophesize(ClassSet::class);

        $archRuleConstraint = $this->prophesize(ArchRuleConstraint::class);
        $archRuleConstraintToGivenClasses = $this->prophesize(ArchRuleGivenClasses::class);
        $archRuleConstraint
            ->get()
            ->willReturn($archRuleConstraintToGivenClasses)
            ->shouldBeCalled();

        $archRuleConstraintToGivenClasses->check($classSet)->shouldBeCalled();
        $archRuleConstraintToGivenClasses->getViolations()->willReturn(new Violations('Violation 2', 'Violation 3'))->shouldBeCalled();

        $archRuleGivenClasses = $this->prophesize(ArchRuleGivenClasses::class);
        $archRuleGivenClasses->check($classSet)->shouldBeCalled();
        $archRuleGivenClasses->getViolations()->willReturn(new Violations('Violation 1'))->shouldBeCalled();


        $ruleChecker = new RuleChecker();
        $ruleChecker
            ->checkThatClassesIn($classSet->reveal())
            ->meetTheFollowingRules($archRuleGivenClasses->reveal(), $archRuleConstraint->reveal());

        self::assertEquals(2, $ruleChecker->assertionsCount());

        $violations = $ruleChecker->run();
        self::assertEquals(
            new Violations('Violation 1', 'Violation 2', 'Violation 3'),
            $violations
        );
    }
}
