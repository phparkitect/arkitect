<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $rootPath = realpath(__DIR__);
    $classSet = ClassSet::fromDir("$rootPath/line_numbers");

    $rules = [
        Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Application'))
            ->should(new DependsOnlyOnTheseNamespaces(['App\Application']))
            ->because('That is how I want it'),
    ];

    $config->add($classSet, ...$rules);
};
