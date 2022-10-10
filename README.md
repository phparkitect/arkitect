# 📐 PHPArkitect 
[![Latest Stable Version](https://poser.pugx.org/phparkitect/phparkitect/v/stable)](https://packagist.org/packages/phparkitect/phparkitect)  ![PHPArkitect](https://github.com/phparkitect/arkitect/workflows/Arkitect/badge.svg?branch=master)
[![Packagist](https://img.shields.io/packagist/dt/phparkitect/phparkitect.svg)](https://packagist.org/packages/phparkitect/phparkitect)
[![codecov](https://codecov.io/gh/phparkitect/arkitect/branch/main/graph/badge.svg)](https://codecov.io/gh/phparkitect/arkitect)


1. [Introduction](#introduction)
1. [Installation](#installation)
1. [Usage](#usage)
1. [Available rules](#available-rules)
1. [Rule Builders](#rule-builders)
1. [Integrations](#integrations)

# Introduction

PHPArkitect helps you to keep your PHP codebase coherent and solid, by permitting to add some architectural constraint check to your workflow.
You can express the constraint that you want to enforce, in simple and readable PHP code, for example:

```php
Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveNameMatching('*Controller'))
    ->because('it\'s a symfony naming convention');
```
# Installation

## Using Composer

```bash
composer require --dev phparkitect/phparkitect
```

## Using a Phar
Sometimes your project can conflict with one or more of PHPArkitect's dependencies. In that case you may find the Phar (a self-contained PHP executable) useful.

The Phar can be downloaded from GitHub:

```
wget https://github.com/phparkitect/arkitect/releases/latest/download/phparkitect.phar
chmod +x phparkitect.phar
./phparkitect.phar check
```

# Usage

To use this tool you need to launch a command via Bash:

```
phparkitect check
```

With this command `phparkitect` will search all rules in the root of your project the default config file called `phparkitect.php`.
You can also specify your configuration file using `--config` option like this:

```
phparkitect check --config=/project/yourConfigFile.php
```

By default, a progress bar will show the status of the ongoing analysis.

## Configuration

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


# Available rules

**Hint**: If you want to test how a Rule work, you can use the command like `phparkitect debug:expression <RuleName> <arguments>` to check which class satisfy the rule in your current folder.

For example: `phparkitect debug:expression ResideInOneOfTheseNamespaces App`

---

Currently, you can check if a class:

### Depends on a namespace

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new DependsOnlyOnTheseNamespaces('App\Domain', 'Ramsey\Uuid'))
    ->because('we want to protect our domain from external dependencies except for Ramsey\Uuid');
```

### Doc block contains a string

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\Events'))
    ->should(new DocBlockContains('@psalm-immutable'))
    ->because('we want to enforce immutability');
```

### Doc block not contains a string

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new DocBlockNotContains('@psalm-immutable'))
    ->because('we don\'t want to enforce immutability');
```

### Extend another class

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new Extend('App\Controller\AbstractController'))
    ->because('we want to be sure that all controllers extend AbstractController');
```

### Has an attribute (requires PHP >= 8.0)

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveAttribute('AsController'))
    ->because('it configures the service container');
```

### Have a name matching a pattern

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Service'))
    ->should(new HaveNameMatching('*Service'))
    ->because('we want uniform naming for services');
```

### Implements an interface

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new Implement('ContainerAwareInterface'))
    ->because('all controllers should be container aware');
```

### Not implements an interface

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\RestApi\Public'))
    ->should(new NotImplement('ContainerAwareInterface'))
    ->because('all public controllers should not be container aware');
```

### Is abstract

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Customer\Service'))
    ->should(new IsAbstract())
    ->because('we want to be sure that classes are abstract in a specific namespace');
```

### Is final

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\Aggregates'))
    ->should(new IsFinal())
    ->because('we want to be sure that aggregates are final classes');
```

### Is not abstract

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new IsNotAbstract())
    ->because('we want to avoid abstract classes into our domain');
```

### Is not final

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\Doctrine'))
    ->should(new IsNotFinal())
    ->because('we want to be sure that our adapters are not final classes');
```

### Not depends on a namespace

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Application'))
    ->should(new NotDependsOnTheseNamespaces('App\Infrastructure'))
    ->because('we want to avoid coupling between application layer and infrastructure layer');
```

### Not extend another class

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller\Admin'))
    ->should(new NotExtend('App\Controller\AbstractController'))
    ->because('we want to be sure that all admin controllers not extend AbstractController for security reasons');
```

### Don't have dependency outside a namespace

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new NotHaveDependencyOutsideNamespace('App\Domain', ['Ramsey\Uuid']))
    ->because('we want protect our domain except for Ramsey\Uuid');
```

### Not have a name matching a pattern

```php
$rules = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App'))
    ->should(new NotHaveNameMatching('*Manager'))
    ->because('*Manager is too vague in naming classes');
```

### Reside in a namespace

```php
$rules = Rule::allClasses()
    ->that(new HaveNameMatching('*Handler'))
    ->should(new ResideInOneOfTheseNamespaces('App\Application'))
    ->because('we want to be sure that all CommandHandlers are in a specific namespace');
```


### Not reside in a namespace

```php
$rules = Rule::allClasses()
    ->that(new Extend('App\Domain\Event'))
    ->should(new NotResideInOneOfTheseNamespaces('App\Application', 'App\Infrastructure'))
    ->because('we want to be sure that all events not reside in wrong layers');
```

You can also define components and ensure that a component:
- should not depend on any component
- may depend on specific components
- may depend on any component

Check out [this demo project](https://github.com/phparkitect/arkitect-demo) to get an idea on how write rules.


# Rule Builders

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
        ->component('Domain')->definedBy('App\Domain\*')

        ->where('Controller')->mayDependOnComponents('Service', 'Entity')
        ->where('Service')->mayDependOnComponents('Repository', 'Entity')
        ->where('Repository')->mayDependOnComponents('Entity')
        ->where('Entity')->shouldNotDependOnAnyComponent()
        ->where('Domain')->shouldOnlyDependOnComponents('Domain')

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
* `--target-php-version`: With this parameter, you can specify which PHP version should use the parser. This can be useful to debug problems and to understand if there are problems with a different PHP version.
Supported PHP versions are: 7.1, 7.2, 7.3, 7.4, 8.0, 8.1
 * `--stop-on-failure`: With this option the process will end immediately after the first violation.

## Run only a specific rule
For some reasons, you might want to run only a specific rule, you can do it using `runOnlyThis` like this:

```php
$rules[] = Rule::allClasses()
    ->except('App\Controller\FolderController\*')
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveNameMatching('*Controller'))
    ->because('we want uniform naming')
    ->runOnlyThis();
```

# Integrations

## Laravel
If you plan to use Arkitect with Laravel, [smortexa](https://github.com/smortexa) wrote a nice wrapper with some predefined rules for laravel: https://github.com/smortexa/laravel-arkitect
