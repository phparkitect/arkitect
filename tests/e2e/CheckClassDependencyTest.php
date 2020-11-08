<?php
declare(strict_types=1);

namespace e2e;

use Arkitect\ClassSet;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class CheckClassDependencyTest extends TestCase
{
    public function test_should_check_code_in_happy_island_does_not_depend_on_outside_code(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/fixtures/happy_island');

        $rule = Rule::classes()
            ->that()
            ->resideInNamespace('App\HappyIsland')
            ->should()
            ->haveNameMatching('Happy*')
            ->get();

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
