<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\ClassSet;
use Arkitect\RuleChecker;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class RuleCheckerTest extends TestCase
{
    public function test_it_returns_violations(): void
    {
        $this->markTestSkipped();

        $classSet = $this->prophesize(ClassSet::class);

        $archRuleGivenClasses = $this->prophesize(ArchRuleGivenClasses::class);
        $archRuleGivenClasses->check($classSet)->shouldBeCalled();
        $archRuleGivenClasses->getViolations()->willReturn(new Violations('Violation 1'))->shouldBeCalled();

        $anotherArchRuleGivenClasses = $this->prophesize(ArchRuleGivenClasses::class);
        $anotherArchRuleGivenClasses->check($classSet)->shouldBeCalled();
        $anotherArchRuleGivenClasses->getViolations()->willReturn(new Violations('Violation 2', 'Violation 3'))->shouldBeCalled();

        $ruleChecker = new RuleChecker();
        $ruleChecker
            ->checkThatClassesIn($classSet->reveal())
            ->meetTheFollowingRules($archRuleGivenClasses->reveal(), $anotherArchRuleGivenClasses->reveal());

        self::assertEquals(2, $ruleChecker->assertionsCount());

        $violations = $ruleChecker->run();
        self::assertEquals(
            new Violations('Violation 1', 'Violation 2', 'Violation 3'),
            $violations
        );
    }
}
