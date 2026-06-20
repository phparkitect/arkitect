# Rules reference

This page documents every built-in rule (expression) and the Component Architecture rule builder.

## Table of contents

- [Namespace rules](#namespace-rules)
- [Naming rules](#naming-rules)
- [Inheritance & implementation](#inheritance--implementation)
- [Traits](#traits)
- [Type checks](#type-checks)
- [Doc blocks & attributes](#doc-blocks--attributes)
- [Component Architecture builder](#component-architecture-builder)

---

## Namespace rules

`Rule::namespace('App\Controller')` is a shortcut for `Rule::allClasses()->that(new ResideInOneOfTheseNamespaces('App\Controller'))`. It accepts multiple namespaces: `Rule::namespace('App\Controller', 'App\Service')`.

### ResideInOneOfTheseNamespaces / NotResideInTheseNamespaces

```php
// Enforce that all handlers live in the application layer
$rules[] = Rule::allClasses()
    ->that(new HaveNameMatching('*Handler'))
    ->should(new ResideInOneOfTheseNamespaces('App\Application'))
    ->because('we want to be sure that all CommandHandlers are in a specific namespace');

// Ensure domain events do not leak into other layers
$rules[] = Rule::allClasses()
    ->that(new Extend('App\Domain\Event'))
    ->should(new NotResideInTheseNamespaces('App\Application', 'App\Infrastructure'))
    ->because('we want to be sure that all events not reside in wrong layers');
```

### ResideInOneOfTheseNamespacesExactly / NotResideInOneOfTheseNamespacesExactly

These rules check namespace membership **without** matching child namespaces. Unlike `ResideInOneOfTheseNamespaces` which matches recursively, these rules only match classes directly in the given namespace.

```php
// Only allow entity classes at the root Entity namespace, not in subdirectories
$rules[] = Rule::allClasses()
    ->that(new HaveNameMatching('*Entity'))
    ->should(new ResideInOneOfTheseNamespacesExactly('App\Domain\Entity'))
    ->because('we want entity classes only in the root Entity namespace, not in subdirectories');

// Prevent classes from sitting directly at the Legacy namespace root
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Legacy'))
    ->should(new NotResideInOneOfTheseNamespacesExactly('App\Legacy'))
    ->because('we want to avoid classes directly in the Legacy namespace root');
```

For example, with namespace `App\Domain\Entity`:
- `App\Domain\Entity\User` ✅ matches `ResideInOneOfTheseNamespacesExactly`
- `App\Domain\Entity\ValueObject\Email` ❌ does not match (child namespace)

### DependsOnlyOnTheseNamespaces / NotDependsOnTheseNamespaces

```php
// Allow only specific external dependencies in the domain
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new DependsOnlyOnTheseNamespaces(['App\Domain', 'Ramsey\Uuid'], ['App\Excluded']))
    ->because('we want to protect our domain from external dependencies except for Ramsey\Uuid');

// Prevent the application layer from depending on infrastructure
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Application'))
    ->should(new NotDependsOnTheseNamespaces(['App\Infrastructure'], ['App\Infrastructure\Repository']))
    ->because('we want to avoid coupling between application layer and infrastructure layer');
```

### NotHaveDependencyOutsideNamespace

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new NotHaveDependencyOutsideNamespace('App\Domain', ['Ramsey\Uuid']))
    ->because('we want to protect our domain except for Ramsey\Uuid');
```

> PHP core classes (e.g. `DateTime`, `Exception`, `PDO`) are automatically excluded from dependency checks.

---

## Naming rules

### HaveNameMatching / NotHaveNameMatching

```php
// Enforce a naming convention
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Service'))
    ->should(new HaveNameMatching('*Service'))
    ->because('we want uniform naming for services');

// Forbid vague names
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App'))
    ->should(new NotHaveNameMatching('*Manager'))
    ->because('*Manager is too vague in naming classes');
```

### MatchOneOfTheseNames

Similar to `HaveNameMatching`, but accepts an array of patterns. The rule passes if the class name matches **any** of the provided patterns.

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new MatchOneOfTheseNames(['*Controller', '*Action']))
    ->because('we want controllers to match one of these naming patterns');
```

---

## Inheritance & implementation

### Extend / NotExtend

`Extend` raises a violation when none of the given classes match; `NotExtend` raises a violation when any of them match.

```php
// All controllers must extend the base class
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new Extend('App\Controller\AbstractController'))
    ->because('we want to be sure that all controllers extend AbstractController');

// Admin controllers must not extend the standard base for security reasons
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller\Admin'))
    ->should(new NotExtend('App\Controller\AbstractController'))
    ->because('we want to be sure that all admin controllers not extend AbstractController for security reasons');
```

### Implement / NotImplement

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new Implement('ContainerAwareInterface'))
    ->because('all controllers should be container aware');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\RestApi\Public'))
    ->should(new NotImplement('ContainerAwareInterface'))
    ->because('all public controllers should not be container aware');
```

### IsA / IsNotA

These rules use PHP's `is_a()` and therefore match both inheritance and interface implementation.

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\Event'))
    ->should(new IsA('App\Domain\DomainEvent'))
    ->because('all events should inherit from or implement DomainEvent');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\Event'))
    ->should(new IsNotA('App\Domain\DeprecatedEvent'))
    ->because('no event should extend or implement the deprecated base class');
```

---

## Traits

### HaveTrait / NotHaveTrait

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('Tests\Feature'))
    ->should(new HaveTrait('Illuminate\Foundation\Testing\DatabaseTransactions'))
    ->because('we want all Feature tests to run transactions');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('Tests\Feature'))
    ->should(new NotHaveTrait('Illuminate\Foundation\Testing\RefreshDatabase'))
    ->because('we want all Feature tests to never refresh the database for performance reasons');
```

---

## Type checks

### IsAbstract / IsNotAbstract

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Customer\Service'))
    ->should(new IsAbstract())
    ->because('we want to be sure that classes are abstract in a specific namespace');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new IsNotAbstract())
    ->because('we want to avoid abstract classes into our domain');
```

### IsFinal / IsNotFinal

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\Aggregates'))
    ->should(new IsFinal())
    ->because('we want to be sure that aggregates are final classes');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\Doctrine'))
    ->should(new IsNotFinal())
    ->because('we want to be sure that our adapters are not final classes');
```

### IsReadonly / IsNotReadonly

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\ValueObjects'))
    ->should(new IsReadonly())
    ->because('we want to be sure that value objects are readonly classes');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\Entity'))
    ->should(new IsNotReadonly())
    ->because('we want to be sure that there are no readonly entities');
```

### IsTrait / IsNotTrait

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Customer\Service\Traits'))
    ->should(new IsTrait())
    ->because('we want to be sure that there are only traits in a specific namespace');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new IsNotTrait())
    ->because('we want to avoid traits in our codebase');
```

### IsInterface / IsNotInterface

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Interfaces'))
    ->should(new IsInterface())
    ->because('we want to be sure that all interfaces are in one directory');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('Tests\Integration'))
    ->should(new IsNotInterface())
    ->because('we want to be sure that we do not have interfaces in tests');
```

### IsEnum / IsNotEnum

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Enum'))
    ->should(new IsEnum())
    ->because('we want to be sure that all classes are enum');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new IsNotEnum())
    ->because('we want to be sure that all classes are not enum');
```

---

## Doc blocks & attributes

### ContainDocBlockLike / NotContainDocBlockLike

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain\Events'))
    ->should(new ContainDocBlockLike('@psalm-immutable'))
    ->because('we want to enforce immutability');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new NotContainDocBlockLike('@psalm-immutable'))
    ->because('we don\'t want to enforce immutability');
```

### HaveAttribute / NotHaveAttribute

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveAttribute('Symfony\Component\HttpKernel\Attribute\AsController'))
    ->because('it configures the service container');

$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new NotHaveAttribute('Deprecated'))
    ->because('deprecated controllers should be removed, not kept in production');
```

---

## Component Architecture builder

Lets you define named components and enforce dependency constraints between them in a readable, declarative style.

```php
<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
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

    // Architecture rules can be combined with regular rules in the same add() call
    $namingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Service'))
        ->should(new HaveNameMatching('*Service'))
        ->because('we want uniform naming');

    $config->add($classSet, $namingRule, ...$layeredArchitectureRules);
};
```

Each component is defined by a glob pattern. The available dependency constraints are:

| Method | Meaning |
|---|---|
| `mayDependOnComponents(...)` | May depend on the listed components (and nothing else). |
| `shouldOnlyDependOnComponents(...)` | Alias for `mayDependOnComponents`. |
| `shouldNotDependOnAnyComponent()` | Must not depend on any other component. |
| `mayDependOnAnyComponent()` | May depend on any component without restriction. |
