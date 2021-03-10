<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class CheckClassNamingTest extends TestCase
{
    public function test_code_in_happy_island_should_have_name_matching_prefix(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/fixtures/happy_island');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\HappyIsland'))
            ->should(new HaveNameMatching('Happy*'))
            ->because("that's what she said");

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
