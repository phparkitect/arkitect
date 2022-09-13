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

        $this->assertInstanceOf(Config::class, $config);

        $classSetRulesExpected[] = ClassSetRules::create($classSet, ...[$rule]);
        $this->assertEquals($classSetRulesExpected, $config->getClassSetRules());
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

        $this->assertInstanceOf(Config::class, $config);

        $classSetRulesExpected[] = ClassSetRules::create($classSet, ...[$rule2]);
        $this->assertEquals($classSetRulesExpected, $config->getClassSetRules());
    }
}
