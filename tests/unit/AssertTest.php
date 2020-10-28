<?php
declare(strict_types=1);

namespace ArkitectTests\unit;

use Arkitect\ArchViolationsException;
use Arkitect\Assert;
use Arkitect\ClassSet;
use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class AssertTest extends TestCase
{
    public function test_it_checks_the_rule_against_the_class_set_and_throws_exception(): void
    {
        $classSet = $this->prophesize(ClassSet::class);
        $rule = $this->prophesize(ArchRuleGivenClasses::class);

        $rule->check($classSet)->shouldBeCalled();

        $rule
            ->getViolations()
            ->willReturn(new Violations('Random violation 1', 'Random violation 2'));

        $assert = new Assert($classSet->reveal(), $rule->reveal());

        self::expectException(ArchViolationsException::class);

        $assert->run();
    }
}
