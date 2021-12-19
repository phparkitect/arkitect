<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/mvc');

    $rule_1 = Rule::allClasses()
        ->except('App\Controller\BaseController')
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new Implement('ContainerAwareInterface'))
        ->because('all controllers should be container aware');

    $rule_2 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('we want uniform naming');

    $rule_3 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new NotHaveDependencyOutsideNamespace('App\Domain'))
        ->because('we want protect our domain');

    $config
        ->add($mvc_class_set, ...[$rule_1, $rule_2, $rule_3]);
};
