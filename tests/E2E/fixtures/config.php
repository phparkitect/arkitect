<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Arkitect\RuleChecker $ruleChecker): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/mvc');

    $rule_1 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new Implement('ContainerAwareInterface'))
        ->because('all controllers should be container aware');

    $rule_2 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('we want uniform naming');

    $ruleChecker
        ->checkThatClassesIn($mvc_class_set)
        ->meetTheFollowingRules($rule_1, $rule_2);

    $happy_island_class_set = ClassSet::fromDir(__DIR__.'/happy_island');

    $a_naming_rule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\HappyIsland'))
        ->should(new HaveNameMatching('Happy*'))
        ->because('every class in the happy island should be happy');

    $ruleChecker
        ->checkThatClassesIn($happy_island_class_set)
        ->meetTheFollowingRules($a_naming_rule);
};
