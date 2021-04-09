<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/mvc');

    $rules = [
        Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
            ->should(new DependsOnlyOnTheseNamespace('App\Domain'))
            ->because('We want order management to be independent from the rest of the application'),
    ];

    $config->add(ClassSetRules::create($mvc_class_set, ...$rules));
};
