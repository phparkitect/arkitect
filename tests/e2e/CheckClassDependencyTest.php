<?php
declare(strict_types=1);

namespace e2e;

use Arkitect\ClassSet;
use Arkitect\DSL\Expression\HaveNameMatching;
use Arkitect\DSL\Expression\ResideInNamespace;
use Arkitect\DSL\Rule;
use Arkitect\PHPUnit\ArchRuleTestCase;
use PHPUnit\Framework\TestCase;

class CheckClassDependencyTest extends TestCase
{
    public function test_should_check_code_in_happy_island_does_not_depend_on_outside_code(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/fixtures/happy_island');

        $rule = Rule::classes()
            ->that(new ResideInNamespace('App\HappyIsland'))
            ->should(new HaveNameMatching('Happy*'))
            ->because('Some weird reason');

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
