<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function(Config $config): void
{
    $classSet = ClassSet::fromDir(__DIR__ . '/src');

    $r1 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Catalog\Application\Service'))
        ->should(new HaveNameMatching('*Service'))
        ->because("we want uniform naming");

    $config->add($classSet, $r1);
};