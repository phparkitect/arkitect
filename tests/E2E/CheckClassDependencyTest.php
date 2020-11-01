<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\ClassSet;
use Arkitect\DSL\Rule;
use Arkitect\Expression\HaveNameMatching;
use Arkitect\Expression\ResideInNamespace;
use Arkitect\PHPUnit\ArchRuleTestCase;
use PHPUnit\Framework\TestCase;

class CheckClassDependencyTest extends TestCase
{
    public function test_should_check_code_in_happy_island_does_not_depend_on_outside_code(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/fixtures/happy_island');

        $rule = Rule::classes()
            ->that(new ResideInNamespace('App\HappyIsland'))
            ->should(new HaveNameMatching('Happy*'))
            ->because('Some weird reason')
            ->get();

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
