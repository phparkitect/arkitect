<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\RuleChecker;
use Arkitect\DSL\Rule;
use Arkitect\Expression\HaveNameMatching;
use Arkitect\Expression\ImplementInterface;
use Arkitect\Expression\ResideInNamespace;

return static function (RuleChecker $ruleChecker): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/mvc');

    $controllers_should_implement_container_aware_interface = Rule::classes()
        ->that(new ResideInNamespace('App\Controller'))
        ->should(new ImplementInterface('ContainerAwareInterface'))
        ->because('DI component can automagically inject the service container');

    $controllers_should_have_name_ending_with_controller = Rule::classes()
        ->that(new ResideInNamespace('App\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('This is a naming convention that helps the team for some reason');

    $ruleChecker
        ->checkThatClassesIn($mvc_class_set)
        ->meetTheFollowingRules(
            $controllers_should_implement_container_aware_interface,
            $controllers_should_have_name_ending_with_controller
        );
};
