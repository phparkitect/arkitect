<?php


namespace e2e\fixtures;


use Arkitect\ClassSet;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\ArchRule;

class CheckClassDependencyTest
{

    public function test_should_check_code_in_happy_island_does_not_depend_on_outside_code(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/fixtures/happy_island');

        $rule = ArchRule::classes()
            ->that()
            ->resideInNamespace('App\HappyIsland')
            ->should()
            ->haveNameMatching('Happy*')
            ->get();

        ArchRuleTestCase::assertArchRule($rule, $set);
    }

}