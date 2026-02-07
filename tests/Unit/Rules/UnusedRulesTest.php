<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Rules\UnusedRules;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class UnusedRulesTest extends TestCase
{
    public function test_rule_match_count_starts_at_zero(): void
    {
        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('controllers should have Controller suffix');

        self::assertSame(0, $rule->getMatchCount());
    }

    public function test_rule_match_count_increments_when_specs_match(): void
    {
        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('controllers should have Controller suffix');

        $matchingClass = ClassDescription::getBuilder('App\Controller\UserController', 'src/Controller/UserController.php')
            ->build();

        $violations = new Violations();
        $rule->check($matchingClass, $violations);

        self::assertSame(1, $rule->getMatchCount());
    }

    public function test_rule_match_count_does_not_increment_when_specs_do_not_match(): void
    {
        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('controllers should have Controller suffix');

        $nonMatchingClass = ClassDescription::getBuilder('App\Service\UserService', 'src/Service/UserService.php')
            ->build();

        $violations = new Violations();
        $rule->check($nonMatchingClass, $violations);

        self::assertSame(0, $rule->getMatchCount());
    }

    public function test_rule_match_count_increments_even_when_constraint_is_violated(): void
    {
        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('controllers should have Controller suffix');

        $matchingClassWithViolation = ClassDescription::getBuilder('App\Controller\UserHandler', 'src/Controller/UserHandler.php')
            ->build();

        $violations = new Violations();
        $rule->check($matchingClassWithViolation, $violations);

        self::assertSame(1, $rule->getMatchCount());
        self::assertCount(1, $violations);
    }

    public function test_rule_describe_contains_because(): void
    {
        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('controllers should have Controller suffix');

        $description = $rule->describe();

        self::assertStringContainsString('because controllers should have Controller suffix', $description);
    }

    public function test_unused_rules_collection_counts_correctly(): void
    {
        $unusedRules = new UnusedRules();

        self::assertCount(0, $unusedRules);

        $rule1 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Legacy'))
            ->should(new HaveNameMatching('*Legacy'))
            ->because('legacy classes naming');

        $rule2 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\OldModule'))
            ->should(new HaveNameMatching('*Old'))
            ->because('old module naming');

        $unusedRules->add($rule1);
        $unusedRules->add($rule2);

        self::assertCount(2, $unusedRules);
    }

    public function test_unused_rules_describe_returns_descriptions(): void
    {
        $unusedRules = new UnusedRules();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Legacy'))
            ->should(new HaveNameMatching('*Legacy'))
            ->because('legacy classes naming');

        $unusedRules->add($rule);

        $descriptions = $unusedRules->describe();

        self::assertCount(1, $descriptions);
        self::assertStringContainsString('because legacy classes naming', $descriptions[0]);
    }

    public function test_unused_rules_is_iterable(): void
    {
        $unusedRules = new UnusedRules();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Legacy'))
            ->should(new HaveNameMatching('*Legacy'))
            ->because('legacy classes naming');

        $unusedRules->add($rule);

        $items = [];
        foreach ($unusedRules as $item) {
            $items[] = $item;
        }

        self::assertCount(1, $items);
        self::assertSame($rule, $items[0]);
    }
}
