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

`ClassSet::fromDir()` accepts one or more directories. PHPArkitect also parses custom DocBlock annotations (`@Assert\NotBlank`, etc.) by default; call `$config->skipParsingCustomAnnotations()` to disable this.

## Commands

### `check`

```
phparkitect check
```

Looks for `phparkitect.php` in the current directory by default. Use `--config` to point to a different file:

```
phparkitect check --config=/project/yourConfigFile.php
```

### `init`

Scaffolds a `phparkitect.php` so you don't have to write it from scratch:

```
phparkitect init [--dest-dir=<path>]
```

If a `phparkitect.php` already exists, the command leaves it untouched.

### `debug:expression`

Lists which classes in a directory satisfy a given expression — handy for testing a rule before adding it to your config:

```
phparkitect debug:expression <Expression> [arguments...]
```

`<Expression>` is the short class name of any expression under `Arkitect\Expression\ForClasses` (see [Available rules](#available-rules)); arguments match its constructor. For example:

```
phparkitect debug:expression ResideInOneOfTheseNamespaces App
```

## Configuration reference

Every setting can be passed as a CLI option or set via the corresponding `Config` method. When both are set, **the CLI option wins**.

| Option | Alias | Config method | Description |
|---|---|---|---|
| `--target-php-version` | `-t` | `targetPhpVersion()` | PHP version the parser targets: `8.0`–`8.5` (default: latest). |
| `--stop-on-failure` | `-s` | `stopOnFailure()` | Stop at the first violation instead of collecting them all. |
| `--format` | `-f` | `format()` | Report format: `text` (default), `json` or `gitlab`. |
| `--autoload` | `-a` | `autoloadFilePath()` | Autoload file to load first. Required for the Phar with custom rules. |
| `--use-baseline` | `-b` | `baselineFilePath()` | Baseline file to ignore known violations. |
| `--skip-baseline` | `-k` | `skipBaseline()` | Ignore the default baseline even if present. |
| `--ignore-baseline-linenumbers` | `-i` | `ignoreBaselineLinenumbers()` | Match the baseline ignoring line numbers. |
| `--config` | `-c` | — | Configuration file to load (default: `phparkitect.php`). |
| `--generate-baseline` | `-g` | — | Write current violations to a baseline file instead of failing. |
| `--verbose` | `-v` | — | Print every parsed file instead of the progress bar. |
| — | — | `skipParsingCustomAnnotations()` | Disable parsing of custom DocBlock annotations; enabled by default. |

### Baseline

If your codebase already has violations you can't fix right now, generate a baseline to ignore them:

```
phparkitect check --generate-baseline
```

This creates `phparkitect-baseline.json`. Subsequent runs pick it up automatically. Use a custom file name with `--generate-baseline=my-baseline.json`, point to it with `--use-baseline=my-baseline.json`, or skip it entirely with `--skip-baseline`.

By default the baseline also checks line numbers — a change before the offending line shifts the number and the check fails. Use `--ignore-baseline-linenumbers` to match violations regardless of line number.

> **Warning**: when ignoring line numbers, PHPArkitect cannot detect if the same rule is violated additional times in the same file.

### Output format

| Format | Description |
|---|---|
| `text` | Default human-readable output. |
| `json` | Machine-readable JSON. Suppresses all output except violations. Suitable for GitHub Actions, SonarQube, etc. |
| `gitlab` | Follows GitLab's [code quality format](https://docs.gitlab.com/ci/testing/code_quality/#code-quality-report-format). Suppresses all output except violations. |

### Using a Phar

If your project conflicts with PHPArkitect's dependencies, use the self-contained Phar:

```
wget https://github.com/phparkitect/arkitect/releases/latest/download/phparkitect.phar
chmod +x phparkitect.phar
./phparkitect.phar check
```

When using the Phar with custom rules that need your project's autoloader:

```
./phparkitect.phar check --autoload=vendor/autoload.php
```

## Available rules

| Category | Examples |
|---|---|
| Namespace | `ResideInOneOfTheseNamespaces`, `NotHaveDependencyOutsideNamespace`, `DependsOnlyOnTheseNamespaces` |
| Naming | `HaveNameMatching`, `NotHaveNameMatching`, `MatchOneOfTheseNames` |
| Inheritance | `Extend`, `NotExtend`, `Implement`, `NotImplement`, `IsA`, `IsNotA` |
| Traits | `HaveTrait`, `NotHaveTrait` |
| Type | `IsFinal`, `IsAbstract`, `IsReadonly`, `IsInterface`, `IsEnum`, `IsTrait` … |
| Doc blocks | `ContainDocBlockLike`, `HaveAttribute` |

→ Full reference with code examples and rule builders: [`docs/rules.md`](docs/rules.md)

## Integrations

If you use Laravel, [smortexa](https://github.com/smortexa) maintains a wrapper with predefined rules: [laravel-arkitect](https://github.com/smortexa/laravel-arkitect).

For a full working example, see the [arkitect-demo](https://github.com/phparkitect/arkitect-demo) project.
