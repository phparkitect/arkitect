# Arkitect 
[![Latest Stable Version](https://poser.pugx.org/phparkitect/phparkitect/v/stable)](https://packagist.org/packages/phparkitect/phparkitect)  ![Arkitect](https://github.com/phparkitect/arkitect/workflows/Arkitect/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/phparkitect/arkitect/branch/main/graph/badge.svg)](https://codecov.io/gh/phparkitect/arkitect)

Arkitect helps you to keep your PHP codebase coherent and solid, by permitting to add some architectural constraint check to your workflow.
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
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $mvcClassSet = ClassSet::fromDir(__DIR__.'/mvc');

    $rules = []

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

By default, a progress bar will show the status of the ongoing analysis.

## Optional parameters and options
You can add parameters when you launch the tool. At the moment you can add these parameters and options: 
* `-v` : with this option you launch Arkitect with the verbose mode to see every parsed file
* `--config`: with this parameter, you can specify your config file instead of the default. like this:
```
phparkitect check --config=/project/yourConfigFile.php
```
* `--target-php-version`: with this parameter, you can specify which PHP version should use the parser. This can be useful to debug problems and to understand if there are problems with a different PHP version.
Supported PHP versions are: 7.1, 7.2, 7.3, 7.4, 8.0, 8.1
