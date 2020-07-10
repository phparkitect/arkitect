<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\Rules\ArchRule;

$mvc_class_set = ClassSet::fromDir(__DIR__ . '/mvc');

$controllers_should_implement_container_aware_interface = ArchRule::classes()
    ->that()
        ->resideInNamespace('App\Controller')
    ->should()
        ->implement('ContainerAwareInterface');

$controllers_should_have_name_ending_with_controller = ArchRule::classes()
    ->that()
        ->resideInNamespace('App\Controller')
    ->should()
        ->haveNameMatching('*Controller');

Arkitect\RuleChecker::checkThatClassesIn($mvc_class_set)->meetTheFollowingRules(
    $controllers_should_implement_container_aware_interface,
    $controllers_should_have_name_ending_with_controller
);


$happy_island_class_set = ClassSet::fromDir(__DIR__ . '/happy_island');

$a_naming_rule = ArchRule::classes()
    ->that()
        ->resideInNamespace('App\HappyIsland')
    ->should()
        ->haveNameMatching('Happy*');

Arkitect\RuleChecker::checkThatClassesIn($happy_island_class_set)->meetTheFollowingRules($a_naming_rule);
