<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\RuleChecker;
use Arkitect\DSL\Rule;
use Arkitect\Expression\HaveNameMatching;
use Arkitect\Expression\ResideInNamespace;

return static function (RuleChecker $ruleChecker): void {
    $happy_island_class_set = ClassSet::fromDir(__DIR__.'/happy_island');

    $a_naming_rule = Rule::classes()
        ->that(new ResideInNamespace('App\HappyIsland'))
        ->should(new HaveNameMatching('Happy*'))
        ->because('For some reason we want to distinguish this classes using naming')
        ->get();

    $ruleChecker
        ->checkThatClassesIn($happy_island_class_set)
        ->meetTheFollowingRules($a_naming_rule);
};
