<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $dependenciesLeakClassSet = ClassSet::fromDir(__DIR__.'/DependenciesLeak');

    $rule_1 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\DependenciesLeak\SecondModule'))
        ->should(new NotDependsOnTheseNamespaces('App\DependenciesLeak\FirstModule'))
        ->because('modules should be independent');

    $config
        ->add($dependenciesLeakClassSet, ...[$rule_1]);
};
