# 📐 PHPArkitect

[![Latest Stable Version](https://poser.pugx.org/phparkitect/phparkitect/v/stable)](https://packagist.org/packages/phparkitect/phparkitect) ![PHPArkitect](https://github.com/phparkitect/arkitect/actions/workflows/build.yml/badge.svg) [![Packagist](https://img.shields.io/packagist/dt/phparkitect/phparkitect.svg)](https://packagist.org/packages/phparkitect/phparkitect) [![codecov](https://codecov.io/gh/phparkitect/arkitect/branch/main/graph/badge.svg)](https://codecov.io/gh/phparkitect/arkitect)

**PHPArkitect** lets you write architectural rules for your PHP codebase as plain PHP code and verify them in CI. Think of it as a test suite for your architecture: if a class in `App\Domain` imports something from `App\Infrastructure`, the check fails.

```php
Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new NotHaveDependencyOutsideNamespace('App\Domain'))
    ->because('the domain must not depend on infrastructure');
```

## Quick Start

**1. Install**

```bash
composer require --dev phparkitect/phparkitect
```

**2. Create a config file**

```bash
vendor/bin/phparkitect init
```

This scaffolds `phparkitect.php` in the current directory. Edit it to add your rules.

**3. Run**

```bash
vendor/bin/phparkitect check
```

PHPArkitect reports every violation with the class name, the broken rule, and the `->because()` message you wrote.

## Core concepts

| Concept | What it is |
|---|---|
| `ClassSet` | The set of PHP files to analyse (`ClassSet::fromDir(__DIR__.'/src')`). |
| `Rule` | A constraint: a selector (`that()`), a check (`should()`), and a reason (`because()`). |
| `Expression` | A single, composable condition — used in both `that()` and `should()`. |

A minimal config file:

```php
<?php
declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('we want uniform naming for controllers');

    $config->add($classSet, ...$rules);
};
```

## Rules at a glance

| Category | Examples |
|---|---|
| Namespace | `ResideInOneOfTheseNamespaces`, `NotHaveDependencyOutsideNamespace`, `DependsOnlyOnTheseNamespaces` |
| Naming | `HaveNameMatching`, `NotHaveNameMatching`, `MatchOneOfTheseNames` |
| Inheritance | `Extend`, `NotExtend`, `Implement`, `NotImplement`, `IsA`, `IsNotA` |
| Traits | `HaveTrait`, `NotHaveTrait` |
| Type | `IsFinal`, `IsAbstract`, `IsReadonly`, `IsInterface`, `IsEnum`, `IsTrait` … |
| Doc blocks | `ContainDocBlockLike`, `HaveAttribute` |

→ **Full reference with examples**: [`docs/rules.md`](docs/rules.md)

## Further reading

| Document | Contents |
|---|---|
| [`docs/rules.md`](docs/rules.md) | Every rule with code examples; the Component Architecture rule builder; `andThat()`, `except()`, `runOnlyThis()`. |
| [`docs/configuration.md`](docs/configuration.md) | All CLI options, the `init` and `debug:expression` commands, baseline support, output formats, Phar usage. |

## Integrations

If you use Laravel, [smortexa](https://github.com/smortexa) maintains a wrapper with predefined rules: [laravel-arkitect](https://github.com/smortexa/laravel-arkitect).

For a full working example, see the [arkitect-demo](https://github.com/phparkitect/arkitect-demo) project.
