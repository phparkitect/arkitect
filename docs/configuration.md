# Configuration reference

This page covers everything you need to configure PHPArkitect: the config file, all CLI options, baseline support, output formats, and the helper commands.

## Table of contents

- [Configuration file](#configuration-file)
- [CLI options](#cli-options)
- [Commands](#commands)
- [Baseline](#baseline)
- [Output format](#output-format)
- [Using a Phar](#using-a-phar)

---

## Configuration file

PHPArkitect is configured via a `phparkitect.php` file in your project root. The file must return a closure that receives a `Config` object:

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
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('we want uniform naming');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new NotHaveDependencyOutsideNamespace('App\Domain'))
        ->because('we want to protect our domain');

    $config->add($classSet, ...$rules);
};
```

`ClassSet::fromDir()` accepts one or more directories:

```php
$classSet = ClassSet::fromDir(__DIR__.'/src', __DIR__.'/lib/my-lib/src');
```

### Custom annotations

PHPArkitect parses custom DocBlock annotations (e.g. `@Assert\NotBlank`, `@Serializer\Expose`) by default. To disable this:

```php
$config->skipParsingCustomAnnotations();
```

---

## CLI options

Every setting can be passed as a CLI option to `phparkitect check`, or set via the corresponding `Config` method. When both are set, **the CLI option wins**.

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
| — | — | `skipParsingCustomAnnotations()` | Disable parsing of custom DocBlock annotations; parsing is enabled by default. |

---

## Commands

### `check`

Runs the architectural checks defined in the config file:

```
phparkitect check
```

By default, `phparkitect` looks for `phparkitect.php` in the current directory. Use `--config` to point to a different file:

```
phparkitect check --config=/project/yourConfigFile.php
```

### `init`

Scaffolds a new `phparkitect.php` configuration file so you don't have to write it from scratch:

```
phparkitect init
```

If a `phparkitect.php` already exists in the target directory, the command leaves it untouched.

| Option | Alias | Description |
|---|---|---|
| `--dest-dir` | `-d` | Directory where the file is created (default: current directory). |

```
phparkitect init --dest-dir=/path/to/dir
```

### `debug:expression`

Lists which classes in a directory satisfy a given expression. Useful for testing how a rule behaves before adding it to your config:

```
phparkitect debug:expression <Expression> [arguments...]
```

`<Expression>` is the short class name of any expression under `Arkitect\Expression\ForClasses` (see the [rules reference](rules.md)); the arguments are the same you would pass to its constructor.

For example, to list every class that resides in the `App` namespace:

```
phparkitect debug:expression ResideInOneOfTheseNamespaces App
```

| Option | Alias | Description |
|---|---|---|
| `--from-dir` | `-d` | Directory to search for classes (default: current directory). |
| `--target-php-version` | `-t` | PHP version the parser targets. |

---

## Baseline

If your codebase already has violations that you can't fix right now, use the baseline feature to tell PHPArkitect to ignore them.

### Creating a baseline

```
phparkitect check --generate-baseline
```

This creates `phparkitect-baseline.json` in the current directory. To use a custom file name:

```
phparkitect check --generate-baseline=my-baseline.json
```

### Using a baseline

If the default `phparkitect-baseline.json` is present it is used automatically. To specify a different file:

```
phparkitect check --use-baseline=my-baseline.json
```

To skip the default baseline:

```
phparkitect check --skip-baseline
```

### Line numbers in the baseline

By default, the baseline also checks line numbers. When a line before the offending line changes, the line number shifts and the check fails even though the violation is the same.

Use `--ignore-baseline-linenumbers` to skip line-number matching:

```
phparkitect check --ignore-baseline-linenumbers
```

> **Warning**: When ignoring line numbers, PHPArkitect cannot detect if the same rule is violated additional times in the same file.

---

## Output format

Control the output format with `--format` (or `-f`):

| Format | Description |
|---|---|
| `text` | Default human-readable output. |
| `json` | Machine-readable JSON. Suppresses all output except violations. Suitable for GitHub Actions, SonarQube, etc. |
| `gitlab` | Follows GitLab's [code quality format](https://docs.gitlab.com/ci/testing/code_quality/#code-quality-report-format). Suppresses all output except violations. |

```
phparkitect check --format=json
```

---

## Using a Phar

If your project conflicts with one or more of PHPArkitect's dependencies, use the self-contained Phar instead:

```
wget https://github.com/phparkitect/arkitect/releases/latest/download/phparkitect.phar
chmod +x phparkitect.phar
./phparkitect.phar check
```

When running as a Phar with custom rules that need your project's autoloader, pass the `--autoload` option:

```
./phparkitect.phar check --autoload=vendor/autoload.php
```
