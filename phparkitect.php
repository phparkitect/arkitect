<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Arkitect\Expression\ForClasses'))
        ->should(new Implement('Arkitect\Expression\Expression'))
        ->because('we want that all rules for classes implement Expression class.');

    $rules[] = Rule::allClasses()
        ->that(new Extend('Symfony\Component\Console\Command\Command'))
        ->should(new ResideInOneOfTheseNamespaces('Arkitect\CLI\Command'))
        ->because('we want find easily all the commands');

    $config
        ->add($classSet, ...$rules);
};
