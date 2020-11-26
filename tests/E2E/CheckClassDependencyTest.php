<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInNamespace;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class CheckClassDependencyTest extends TestCase
{
    public function test_should_check_code_in_happy_island_does_not_depend_on_outside_code(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/fixtures/happy_island');

        $rule = Rule::allClasses()
            ->that(new ResideInNamespace('App\HappyIsland'))
            ->should(new HaveNameMatching('Happy*'))
            ->because("that's what she said");

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
