<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/mvc');

    $rule_1 = Rule::allClasses()
        ->except('App\Services\Foo')
        ->that(new ResideInOneOfTheseNamespaces('App\Services'))
        ->andThat(new ResideInOneOfTheseNamespaces('App\*\Services'))
        ->should(new HaveNameMatching('*Service'))
        ->because('all services should be end name with Service');

    $config
        ->add($mvc_class_set, [$rule_1]);
};
