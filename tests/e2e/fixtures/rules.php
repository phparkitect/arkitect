<?php
declare(strict_types=1);

use Arkitect\Arkitect;
use Arkitect\ClassSet;
use Arkitect\Rules\ArchRule;

Arkitect::checkThatClassesInThis(
    ClassSet::fromDir(__DIR__ . '/mvc')
)->meetTheFollowingRules(
    ArchRule::classes()
        ->that()
            ->resideInNamespace('App\Controller')
        ->should()
            ->implement('ContainerAwareInterface'),

    ArchRule::classes()
        ->that()
            ->resideInNamespace('App\Controller')
        ->should()
            ->haveNameMatching('*Controller')
);


Arkitect::checkThatClassesInThis(
    ClassSet::fromDir(__DIR__ . '/happy_island')
)->meetTheFollowingRules(
    ArchRule::classes()
        ->that()
            ->resideInNamespace('App\HappyIsland')
        ->should()
            ->haveNameMatching('Happy*')
);