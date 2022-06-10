# PHPArkitect 
[![Latest Stable Version](https://poser.pugx.org/phparkitect/phparkitect/v/stable)](https://packagist.org/packages/phparkitect/phparkitect)  ![PHPArkitect](https://github.com/phparkitect/arkitect/workflows/Arkitect/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/phparkitect/arkitect/branch/main/graph/badge.svg)](https://codecov.io/gh/phparkitect/arkitect)

PHPArkitect helps you to keep your PHP codebase coherent and solid, by permitting to add some architectural constraint check to your workflow.
You can express the constraint that you want to enforce, in simple and readable PHP code, for example:

```php
Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveNameMatching('*Controller'))
    ->because("it's a symfony naming convention");
```

## What kind of rules can I enforce with Arkitect

Currently, you can check if a class:
 - depends on a namespace
 - extends another class
 - not extends another class
 - have a name matching a pattern
 - not have a name matching a pattern
 - implements an interface
 - not implements an interface
 - depends on a namespace
 - don't have dependency outside a namespace
 - reside in a namespace
 - not reside in a namespace
 - is final
 - is not final
 - is abstract
 - is not abstract
 - doc block contains a string
 - doc block not contains a string

You can also define components and ensure that a component:
- should not depend on any component
- may depend on specific components
- may depend on any component

Check out [this demo project](https://github.com/phparkitect/arkitect-demo) to get an idea on how write rules

# How to install

## Using composer

```bash
composer require --dev phparkitect/phparkitect
```

## Using a phar
Sometimes your project can conflict with one or more of Phparkitect's dependencies. In that case you may find the Phar (a self-contained PHP executable) useful.

The Phar can be downloaded from GitHub:

```
wget https://github.com/phparkitect/arkitect/releases/latest/download/phparkitect.phar
chmod +x phparkitect.phar
./phparkitect.phar check
```

# How to use it

To use this tool you need to launch a command via bash:

```
phparkitect check
```

With this command `phparkitect` will search all rules in the root of your project the default config file called `phparkitect.php`.
You can also specify your configuration file using `--config` option like this:

```
phparkitect check --config=/project/yourConfigFile.php
```

By default, a progress bar will show the status of the ongoing analysis.

# Configuration

Example of configuration file `phparkitect.php`

```php
<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvcClassSet = ClassSet::fromDir(__DIR__.'/mvc');

    $rules = [];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('we want uniform naming');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new NotHaveDependencyOutsideNamespace('App\Domain'))
        ->because('we want protect our domain');

    $config
        ->add($mvcClassSet, ...$rules);
};
```

## Rule Builders

PHPArkitect offers some builders that enable you to implement more readable rules for specific contexts. 

### Component Architecture Rule Builder

Thanks to this builder you can define components and enforce dependency constraints between them in a more readable fashion.

```php
<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\RuleBuilders\Architecture\Architecture;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $layeredArchitectureRules = Architecture::withComponents()
        ->component('Controller')->definedBy('App\Controller\*')
        ->component('Service')->definedBy('App\Service\*')
        ->component('Repository')->definedBy('App\Repository\*')
        ->component('Entity')->definedBy('App\Entity\*')

        ->where('Controller')->mayDependOnComponents('Service', 'Entity')
        ->where('Service')->mayDependOnComponents('Repository', 'Entity')
        ->where('Repository')->mayDependOnComponents('Entity')
        ->where('Entity')->shouldNotDependOnAnyComponent()

        ->rules();
        
    // Other rule definitions...

    $config->add($classSet, $serviceNamingRule, $repositoryNamingRule, ...$layeredArchitectureRules);
};
```

### Excluding classes when parser run
If you want to exclude some classes from the parser you can use the `except` function inside your config file like this:

```php
$rules[] = Rule::allClasses()
    ->except('App\Controller\FolderController\*')
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveNameMatching('*Controller'))
    ->because('we want uniform naming');
```

You can use wildcards or the exact name of a class.

## Optional parameters and options
You can add parameters when you launch the tool. At the moment you can add these parameters and options: 
* `-v` : with this option you launch Arkitect with the verbose mode to see every parsed file
* `--config`: with this parameter, you can specify your config file instead of the default. like this:
```
phparkitect check --config=/project/yourConfigFile.php
```
* `--target-php-version`: with this parameter, you can specify which PHP version should use the parser. This can be useful to debug problems and to understand if there are problems with a different PHP version.
Supported PHP versions are: 7.1, 7.2, 7.3, 7.4, 8.0, 8.1
* `-stop-on-failure` : with this option if there is a violation, the process will end immediately
