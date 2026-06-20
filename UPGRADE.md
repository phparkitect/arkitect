# Upgrade guide

This document lists the **breaking changes** you need to address when upgrading
PHPArkitect, ordered from the most recent version to the oldest.

> If a release is **not** listed here, it contains no breaking changes and you
> can upgrade to it without modifying your configuration.

## 1.0.0

### PHP 7 support dropped

The minimum supported PHP version is now **8.0**. If you are still on PHP 7.x,
stay on the `0.8.x` line.

### `--autoload` is mandatory when running as a Phar

When running the Phar, you must now pass the autoload file explicitly:

```diff
- php phparkitect.phar check
+ php phparkitect.phar check --autoload vendor/autoload.php
```

### `*` in `excludePath()` no longer crosses directory separators

`ClassSet::excludePath()` was reworked so that `*` matches within a single
directory segment. Use the new `**` wildcard to restore the previous greedy
behaviour that matched across any number of directory levels:

```diff
- $set->excludePath('src/*/Test.php');   // used to match src/A/B/C/Test.php
+ $set->excludePath('src/**/Test.php');  // matches at any depth
```

Most simple patterns (`Tests/*`, `*Test.php`) are unaffected, because they are
consumed as a substring match.

### User-defined classes in the global namespace are now evaluated

PHP core classes are now auto-excluded from dependency checks via reflection
(`isInternal()`), so you no longer need to list `\Exception`, `\DateTime`,
`MongoDB\Driver\Manager`, etc. in your rules.

As a consequence, the previous "skip everything in the root namespace" shortcut
in `DependsOnlyOnTheseNamespaces` and `NotDependsOnTheseNamespaces` was removed.
**User-defined** classes in the global namespace are now evaluated against your
rules — they used to be silently skipped.

### Docker image no longer published

The PHPArkitect Docker image is no longer published. Existing tags on Docker Hub
remain available, but no new ones will be pushed. Use Composer or the released
Phar instead.

## 0.6.0

### `DependsOnlyOnTheseNamespaces` and `NotDependsOnTheseNamespaces` take an array

These two expressions no longer accept a variadic list of namespaces; pass an
array instead:

```diff
- new DependsOnlyOnTheseNamespaces('App\Domain', 'App\Infrastructure')
+ new DependsOnlyOnTheseNamespaces(['App\Domain', 'App\Infrastructure'])

- new NotDependsOnTheseNamespaces('App\Domain', 'App\Infrastructure')
+ new NotDependsOnTheseNamespaces(['App\Domain', 'App\Infrastructure'])
```
