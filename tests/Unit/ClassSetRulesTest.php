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
    /** @var ClassSet */
    private $classSet;
    /** @var \Arkitect\Rules\DSL\ArchRule */
    private $rule_1;
    /** @var \Arkitect\Rules\DSL\ArchRule */
    private $rule_2;

    protected function setUp(): void
    {
        $this->classSet = ClassSet::fromDir(__DIR__.'/../E2E/fixtures/happy_island');

        $this->rule_1 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new Implement('ContainerAwareInterface'))
            ->because('all controllers should be container aware');

        $this->rule_2 = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('we want uniform naming');
    }

    public function test_create_class_set_rules_correctly(): void
    {
        $rules = [$this->rule_1, $this->rule_2];

        $classSetRules = ClassSetRules::create($this->classSet, $rules);

        $this->assertEquals($this->classSet, $classSetRules->getClassSet());
        $this->assertEquals($rules, $classSetRules->getRules());
    }

    public function test_filter_rules(): void
    {
        $rules = [
            'foo' => $this->rule_1,
            'bar' => $this->rule_2,
        ];

        $classSetRules = ClassSetRules::create($this->classSet, $rules);

        $this->assertEquals($this->rule_1, $classSetRules->getRulesByName('foo'));
        $this->assertNull($classSetRules->getRulesByName('notExistingRule'));
    }

    public function test_filter_rules_return_null_if_rule_not_exists(): void
    {
        $rules = [
            'foo' => $this->rule_1,
            'bar' => $this->rule_2,
        ];

        $classSetRules = ClassSetRules::create($this->classSet, $rules);

        $this->assertNull($classSetRules->getRulesByName('notExistingRule'));
    }
}
