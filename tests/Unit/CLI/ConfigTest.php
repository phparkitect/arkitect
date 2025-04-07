<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function test_it_should_create_config(): void
    {
        $classSet = ClassSet::fromDir(__DIR__.'/foo');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('all controllers should be end name with Controller');

        $config = new Config();
        $config->add($classSet, ...[$rule]);

        self::assertInstanceOf(Config::class, $config);

        $classSetRulesExpected[] = ClassSetRules::create($classSet, ...[$rule]);
        self::assertEquals($classSetRulesExpected, $config->getClassSetRules());
    }

    public function test_it_should_create_config_with_only_one_rule_to_run(): void
    {
        $classSet = ClassSet::fromDir(__DIR__.'/foo');

        $rule1 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('all controllers should be end name with Controller');

        $rule2 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Service'))
            ->should(new HaveNameMatching('*Service'))
            ->because('all services should be end name with Service')
            ->runOnlyThis();

        $config = new Config();
        $config->add($classSet, ...[$rule1, $rule2]);

        self::assertInstanceOf(Config::class, $config);

        $classSetRulesExpected[] = ClassSetRules::create($classSet, ...[$rule2]);
        self::assertEquals($classSetRulesExpected, $config->getClassSetRules());
    }

    public function test_it_should_allow_to_change_the_default_value_for_parsing_custom_annotations(): void
    {
        $config = new Config();
        self::assertTrue($config->isParseCustomAnnotationsEnabled());

        $config->skipParsingCustomAnnotations();
        self::assertFalse($config->isParseCustomAnnotationsEnabled());
    }
}
