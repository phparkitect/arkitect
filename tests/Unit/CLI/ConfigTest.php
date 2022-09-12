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
    protected function setUp(): void
    {
        $this->classSet = ClassSet::fromDir(__DIR__.'/foo');

        $this->rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('all controllers should be end name with Controller');

        $this->config = new Config();
    }

    public function test_it_should_create_config(): void
    {
        $this->config->add($this->classSet, [$this->rule]);

        $this->assertInstanceOf(Config::class, $this->config);

        $classSetRulesExpected[] = ClassSetRules::create($this->classSet, [$this->rule]);
        $this->assertEquals($classSetRulesExpected, $this->config->getClassSetRules());
    }

    public function test_it_should_create_config_with_rule_filtered(): void
    {
        $this->config->add($this->classSet, [
            'myRule' => $this->rule,
        ]);

        $this->assertInstanceOf(Config::class, $this->config);

        $rules = ['myRule' => $this->rule];
        $classSetRulesExpected[] = ClassSetRules::create($this->classSet, $rules);
        $this->assertEquals($classSetRulesExpected, $this->config->getClassSetRules('myRule'));
    }

    public function test_it_should_create_config_with_not_existing_rule_filtered(): void
    {
        $rule = ['myRule' => $this->rule];
        $this->config->add($this->classSet, $rule);

        $this->assertInstanceOf(Config::class, $this->config);
        $this->assertEquals([], $this->config->getClassSetRules('notExistingRule'));
    }
}
