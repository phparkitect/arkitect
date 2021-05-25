# Arkitect [![Latest Stable Version](https://poser.pugx.org/phparkitect/phparkitect/v/stable)](https://packagist.org/packages/phparkitect/phparkitect)  ![Arkitect](https://github.com/phparkitect/arkitect/workflows/Arkitect/badge.svg?branch=master)

Arkitect helps you to keep your PHP codebase coherent and solid, by permitting to add some architectural costraint check to your workflow.
You can express the costraint that you want to enforce, in simple and readable PHP code, for example:

```php
Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveNameMatching('*Controller'))
    ->because("it's a symfony naming convention");
```

## What kind of rules can I enforce with Arkitect

Currently you can check if a class:
 - implements an interface
 - have a name matching a pattern
 - depends on a namespace
 - don't have dependency outside a namespace 

# How to install

## Using composer

```bash
composer require phparkitect/phparkitect
```

## Using a phar
Sometimes your project can conflict with one or more of Phparkitect's dependencies. In that case you may find the Phar (a self-contained PHP executable) useful.

The Phar can be downloaded from Github:

```
wget https://github.com/phparkitect/arkitect/releases/latest/download/phparkitect.phar
chmod +x phparkitect.phar
./phparkitect.phar check
```

## Using docker

If you would like to use Arkitect with Docker you can launch this command:

```
docker run --rm -it -v $(PWD):/project phparkitect/phparkitect:latest check --config=/project/yourConfigFile.php
```
If you have a project with an incompatible version of PHP with Arkitect, using Docker can help you use Arkitect despite the PHP version.

# How to use it

To use this tool you need to launch a command via bash or with Docker like this:

```
phparkitect check
```

With this command `phparkitect` will search all rules in the root of your project the default config file called `phparkitect.php`.
You can also specify your configuration file using `--config` option like this:

```
phparkitect check --config=/project/yourConfigFile.php
```

Example of configuration file `phparkitect.php`

```php
<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvc_class_set = ClassSet::fromDir(__DIR__.'/mvc');

    $rule_1 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('we want uniform naming');

    $rule_2 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new NotHaveDependencyOutsideNamespace('App\Domain'))
        ->because('we want protect our domain');

    $config
        ->add($mvc_class_set, ...[$rule_1, $rule_2]);
};
```

By default, a progress bar will show the status of the ongoing analysis. If you like to get more informaitions on what is happening you can pass the verbose option `-v` on the command line
