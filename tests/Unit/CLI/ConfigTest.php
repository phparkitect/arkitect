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
}
