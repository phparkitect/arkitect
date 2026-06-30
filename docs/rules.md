# Rules reference

This page documents every built-in rule (expression) available in PHPArkitect: what it checks, its constructor signature, and a runnable example.

## Imports

Every example below builds on these imports. The fluent builder and class set live under the `Arkitect\` root; **every expression** (`ResideInOneOfTheseNamespaces`, `IsFinal`, `HaveAttribute`, …) lives under `Arkitect\Expression\ForClasses`, so its fully-qualified name is always `Arkitect\Expression\ForClasses\<ExpressionName>`.

```php
use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Rules\Rule;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
// …and one `use Arkitect\Expression\ForClasses\<Name>;` per expression you use
```

The snippets assume you are inside the config callback, appending to a `$rules` array that is later passed to `$config->add($classSet, ...$rules)` (see the [README](../README.md) for the full file skeleton).

## Table of contents

- [Namespace rules](#namespace-rules)
  - [ResideInOneOfTheseNamespaces / NotResideInTheseNamespaces](#resideinoneofthesenamespaces--notresideinthesenamespaces)
  - [ResideInOneOfTheseNamespacesExactly / NotResideInOneOfTheseNamespacesExactly](#resideinoneofthesenamespacesexactly--notresideinoneofthesenamespacesexactly)
  - [DependsOnlyOnTheseNamespaces / NotDependsOnTheseNamespaces](#dependsonlyonthesenamespaces--notdependsonthesenamespaces)
  - [NotHaveDependencyOutsideNamespace](#nothavedependencyoutsidenamespace)
- [Naming rules](#naming-rules)
  - [HaveNameMatching / NotHaveNameMatching](#havenamematching--nothavenamematching)
  - [MatchOneOfTheseNames](#matchoneofthesenames)
- [Inheritance & implementation](#inheritance--implementation)
  - [Extend / NotExtend](#extend--notextend)
  - [Implement / NotImplement](#implement--notimplement)
  - [IsA / IsNotA](#isa--isnota)
- [Traits](#traits)
  - [HaveTrait / NotHaveTrait](#havetrait--nothavetrait)
- [Type checks](#type-checks)
  - [IsAbstract / IsNotAbstract](#isabstract--isnotabstract)
  - [IsFinal / IsNotFinal](#isfinal--isnotfinal)
  - [IsReadonly / IsNotReadonly](#isreadonly--isnotreadonly)
  - [IsTrait / IsNotTrait](#istrait--isnottrait)
  - [IsInterface / IsNotInterface](#isinterface--isnotinterface)
  - [IsEnum / IsNotEnum](#isenum--isnotenum)
- [Doc blocks & attributes](#doc-blocks--attributes)
  - [ContainDocBlockLike / NotContainDocBlockLike](#containdocblocklike--notcontaindocblocklike)
  - [HaveAttribute / NotHaveAttribute](#haveattribute--nothaveattribute)

---

## Namespace rules

`Rule::namespace('App\Controller')` is a shortcut for `Rule::allClasses()->that(new ResideInOneOfTheseNamespaces('App\Controller'))`. It accepts multiple namespaces: `Rule::namespace('App\Controller', 'App\Service')`.

### ResideInOneOfTheseNamespaces / NotResideInTheseNamespaces

`ResideInOneOfTheseNamespaces` raises a violation when a class does **not** live in any of the given namespaces; `NotResideInTheseNamespaces` raises one when it lives in any of them. Matching is **recursive** — `App\Domain` also matches `App\Domain\Event\UserRegistered`.

```php
new ResideInOneOfTheseNamespaces(string ...$namespaces)
new NotResideInTheseNamespaces(string ...$namespaces)
```

- `$namespaces` — one or more namespace prefixes (variadic).

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

Like the rules above, but matching is **not** recursive: only classes sitting *directly* in the given namespace match, not those in child namespaces.

```php
new ResideInOneOfTheseNamespacesExactly(string ...$namespaces)
new NotResideInOneOfTheseNamespacesExactly(string ...$namespaces)
```

- `$namespaces` — one or more exact namespaces (variadic).

For example, with namespace `App\Domain\Entity`:
- `App\Domain\Entity\User` ✅ matches `ResideInOneOfTheseNamespacesExactly`
- `App\Domain\Entity\ValueObject\Email` ❌ does not match (child namespace)

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

### DependsOnlyOnTheseNamespaces / NotDependsOnTheseNamespaces

`DependsOnlyOnTheseNamespaces` raises a violation when a class depends on **any** namespace outside the allow-list. `NotDependsOnTheseNamespaces` raises one when a class depends on **any** of the forbidden namespaces.

```php
new DependsOnlyOnTheseNamespaces(array $namespaces = [], array $exclude = [])
new NotDependsOnTheseNamespaces(array $namespaces, array $exclude = [])
```

- `$namespaces` — the allowed (resp. forbidden) namespaces.
- `$exclude` — namespaces/classes whose dependencies are **not** checked by this rule (an escape hatch for known exceptions).

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

Raises a violation when a class depends on anything outside its own namespace (plus an optional allow-list of external dependencies).

```php
new NotHaveDependencyOutsideNamespace(string $namespace, array $externalDependenciesToExclude = [])
```

- `$namespace` — the namespace the class is allowed to depend on.
- `$externalDependenciesToExclude` — extra namespaces that are tolerated as dependencies.

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

`HaveNameMatching` raises a violation when the class name does **not** match the pattern; `NotHaveNameMatching` raises one when it **does**. The pattern supports `*` as a wildcard.

```php
new HaveNameMatching(string $name)
new NotHaveNameMatching(string $name)
```

- `$name` — a name pattern, e.g. `*Service`, `Abstract*`.

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

Like `HaveNameMatching`, but accepts several patterns — the rule passes if the class name matches **any** of them.

```php
new MatchOneOfTheseNames(array $names)
```

- `$names` — an array of name patterns.

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new MatchOneOfTheseNames(['*Controller', '*Action']))
    ->because('we want controllers to match one of these naming patterns');
```

---

## Inheritance & implementation

### Extend / NotExtend

`Extend` raises a violation when the class extends **none** of the given classes; `NotExtend` raises one when it extends **any** of them.

```php
new Extend(string ...$classNames)
new NotExtend(string ...$classNames)
```

- `$classNames` — one or more fully-qualified parent class names (variadic).

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

`Implement` raises a violation when the class does **not** implement the interface; `NotImplement` raises one when it **does**.

```php
new Implement(string $interface)
new NotImplement(string $interface)
```

- `$interface` — the fully-qualified interface name.

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

These rules use PHP's `is_a()` semantics, so they match **both** inheritance and interface implementation. `IsA` raises a violation when the class is not an instance of the given type; `IsNotA` raises one when it is.

```php
new IsA(string $allowedFqcn)
new IsNotA(string $disallowedFqcn)
```

- `$allowedFqcn` / `$disallowedFqcn` — the fully-qualified class or interface name.

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

`HaveTrait` raises a violation when the class does **not** use the trait; `NotHaveTrait` raises one when it **does**.

```php
new HaveTrait(string $trait)
new NotHaveTrait(string $trait)
```

- `$trait` — the fully-qualified trait name.

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

The type checks take **no arguments** — pair them with a selector via `that()`.

### IsAbstract / IsNotAbstract

Raise a violation when the class is (resp. is not) declared `abstract`.

```php
new IsAbstract()
new IsNotAbstract()
```

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

Raise a violation when the class is (resp. is not) declared `final`.

```php
new IsFinal()
new IsNotFinal()
```

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

Raise a violation when the class is (resp. is not) declared `readonly` (PHP 8.2+).

```php
new IsReadonly()
new IsNotReadonly()
```

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

Raise a violation when the type is (resp. is not) a `trait`.

```php
new IsTrait()
new IsNotTrait()
```

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

Raise a violation when the type is (resp. is not) an `interface`.

```php
new IsInterface()
new IsNotInterface()
```

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

Raise a violation when the type is (resp. is not) an `enum` (PHP 8.1+).

```php
new IsEnum()
new IsNotEnum()
```

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

`ContainDocBlockLike` raises a violation when the class docblock does **not** contain the given text; `NotContainDocBlockLike` raises one when it **does**.

```php
new ContainDocBlockLike(string $docBlock)
new NotContainDocBlockLike(string $docBlock)
```

- `$docBlock` — the substring/annotation to look for in the class docblock.

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

`HaveAttribute` raises a violation when the class does **not** carry the given PHP attribute; `NotHaveAttribute` raises one when it **does**.

```php
new HaveAttribute(string $attribute)
new NotHaveAttribute(string $attribute)
```

- `$attribute` — the attribute class name (fully-qualified, or short name if unambiguous).

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
