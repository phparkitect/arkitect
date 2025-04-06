<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class ClassSetRulesTest extends TestCase
{
    public function test_create_class_set_rules_correctly(): void
    {
        $classSet = ClassSet::fromDir(__DIR__.'/../E2E/fixtures/happy_island');

        $rule_1 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new Implement('ContainerAwareInterface'))
            ->because('all controllers should be container aware');

        $rule_2 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('we want uniform naming');

        $rules = [$rule_1, $rule_2];

        $classSetRules = ClassSetRules::create($classSet, ...$rules);

        self::assertEquals($classSet, $classSetRules->getClassSet());
        self::assertEquals($rules, $classSetRules->getRules());
    }
}
