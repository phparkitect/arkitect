<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src/Expression/ForClasses');

    $rules = [];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Arkitect\Expression\ForClasses'))
        ->should(new Implement('Arkitect\Expression\Expression'))
        ->because('we want that all rules for classes implement Expression class.');

    $config
        ->add($classSet, ...$rules);
};
