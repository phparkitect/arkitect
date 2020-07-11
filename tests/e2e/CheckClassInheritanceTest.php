<?php
declare(strict_types=1);

namespace ArkitectTests\e2e;

use Arkitect\ClassSet;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\ArchRule;
use PHPUnit\Framework\TestCase;

class CheckClassInheritanceTest extends TestCase
{
    public function test_rule_failing()
    {
        $set = ClassSet::fromDir(__DIR__ . '/fixtures/mvc');

        $rule = ArchRule::classes()
            ->that()
                ->resideInNamespace('App\Controller')
            ->should()
                ->doNotExtendClass('ReallyBadBaseController')
            ->get();

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
