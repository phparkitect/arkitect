<?php declare(strict_types=1);

namespace ArkitectTests;

use Arkitect\ClassSet;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\ArchRule;

class CheckClassesTest extends ArchRuleTestCase
{
    public function test_should_run_checks_on_a_class_set(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/fixtures/mvc');

        $rule = ArchRule::classes()
            ->that()
                ->resideInNamespace('App\Controller')
            ->should()
                ->implement('ContainerAwareInterface')
            ->get();

        $this->assertArchRule($rule, $set);
    }

    public function test_should_check_code_in_happy_island_does_not_depend_on_outside_code(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/fixtures/happy_island');

        $rule = ArchRule::classes()
            ->that()
                ->resideInNamespace('App\HappyIsland')
            ->should()
                ->haveNameMatching('Happy*')
            ->get();

        $this->assertArchRule($rule, $set);
    }

}
