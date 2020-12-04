<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInNamespace;
use Arkitect\Rules\Rule;

return static function (Arkitect\Config $ruleChecker): void {
    $happy_island_class_set = ClassSet::fromDir(__DIR__.'/happy_island');

    $a_naming_rule = Rule::allClasses()
        ->that(new ResideInNamespace('App\HappyIsland'))
        ->should(new HaveNameMatching('Happy*'))
        ->because('every class in the happy island should be happy');

    $ruleChecker
        ->checkThatClassesIn($happy_island_class_set)
        ->meetTheFollowingRules($a_naming_rule);
};
