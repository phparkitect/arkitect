<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures;

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/MvcExample');

    $rule_1 = Rule::allClasses()
        ->except('Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\BaseController')
        ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\E2E\Fixtures\MvcExample\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('all controllers should be end name with Controller');

    $config
        ->add($mvc_class_set, ...[$rule_1]);
};
