<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\DSL\Expression\HaveNameMatching;
use Arkitect\DSL\Expression\ImplementInterface;
use Arkitect\DSL\Expression\ResideInNamespace;
use Arkitect\DSL\Rule;
use Arkitect\RuleChecker;

return static function (RuleChecker $ruleChecker): void {
    $happy_island_class_set = ClassSet::fromDir(__DIR__ . '/happy_island');

    $a_naming_rule = Rule::classes()
        ->that(new ResideInNamespace('App\HappyIsland'))
        ->should(new HaveNameMatching('Happy*'))
        ->because('For some reason we want to distinguish this classes using naming');

    $ruleChecker
        ->checkThatClassesIn($happy_island_class_set)
        ->meetTheFollowingRules($a_naming_rule);
};
