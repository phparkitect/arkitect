<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/mvcMatchBug');

    $rule_1 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new Implement('ContainerAwareInterface'))
        ->because('all controllers should be container aware');

    $config
        ->add(ClassSetRules::create($mvc_class_set, ...[$rule_1]));
};
