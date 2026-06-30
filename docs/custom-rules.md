# Writing custom rules

The built-in rules cover the common cases, but some checks only make sense for *your* project. PHPArkitect treats this as a first-class path, not a workaround: a rule is just a class implementing the `Expression` interface, and once written it plugs into `that()` and `should()` exactly like any built-in.

If a check is specific to one codebase, write a custom expression instead of asking for it in the core — that's the intended way to extend the tool.

## The `Expression` interface

An expression is one composable boolean check over a single class. The interface lives at `Arkitect\Expression\Expression`:

```php
interface Expression
{
    public function describe(ClassDescription $theClass, string $because): Description;

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void;
}
```

- **`describe()`** returns a human-readable `Description` of what the rule expects. It is used to build the violation message, so write it as the *positive* expectation (e.g. "X should be final").
- **`evaluate()`** is where the check happens. If the class violates the rule, add a `Violation` to `$violations`; if it passes, do nothing.

There is also an **optional** `appliesTo(ClassDescription $theClass): bool` method. It is deliberately *not* in the interface so you can skip it, but if you implement it the rule only runs on classes for which it returns `true` — handy to narrow a rule (e.g. `IsFinal` ignores interfaces, traits and enums).

## What you can inspect: `ClassDescription`

Both methods receive a `ClassDescription`, the parsed model of the class under analysis. The main accessors:

| Method | Returns | What it is |
|---|---|---|
| `getName()` | `string` | Short class name |
| `getFQCN()` | `string` | Fully-qualified class name |
| `getFilePath()` | `string` | Path of the file being analysed |
| `getDependencies()` | `list<ClassDependency>` | The types the class references |
| `getExtends()` | `list<FullyQualifiedClassName>` | Parent class(es) |
| `getInterfaces()` | `list<FullyQualifiedClassName>` | Implemented interfaces |
| `getTraits()` | `list<FullyQualifiedClassName>` | Used traits |
| `getAttributes()` | `list<FullyQualifiedClassName>` | PHP attributes on the class |
| `getDocBlock()` | `array` | The class docblock annotations |
| `isAbstract()` / `isFinal()` / `isReadonly()` | `bool` | Class modifiers |
| `isInterface()` / `isTrait()` / `isEnum()` | `bool` | Type of declaration |
| `hasTrait(string $pattern)` / `hasAttribute(string $pattern)` | `bool` | Membership checks (pattern supports `*`) |

## A minimal example: `IsFinal`

The built-in `IsFinal` is the smallest complete expression and a good template:

```php
<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class IsFinal implements Expression
{
    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("{$theClass->getName()} should be final", $because);
    }

    public function appliesTo(ClassDescription $theClass): bool
    {
        // skip the kinds of types where "final" is meaningless
        return !($theClass->isInterface() || $theClass->isTrait() || $theClass->isEnum());
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($theClass->isFinal()) {
            return; // the class satisfies the rule, nothing to report
        }

        $violation = Violation::create(
            $theClass->getFQCN(),
            ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
            $theClass->getFilePath()
        );

        $violations->add($violation);
    }
}
```

The two helpers you'll always use in `evaluate()`:

- `ViolationMessage::selfExplanatory($description)` turns the `Description` into the violation message (the `because` reason is folded in automatically).
- `Violation::create($fqcn, $message, $filePath)` builds the violation. Use `Violation::createWithErrorLine($fqcn, $message, $line, $filePath)` instead when you can point at a specific line.

## Writing your own: a coupling budget

Custom expressions shine when you want something the built-ins don't offer. Here's a rule that fails any class with too many dependencies — a simple coupling budget — taking the limit as a constructor argument:

```php
<?php

declare(strict_types=1);

namespace App\Arch;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class HaveAtMostDependencies implements Expression
{
    public function __construct(private int $max)
    {
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description(
            "{$theClass->getName()} should depend on at most {$this->max} classes",
            $because
        );
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if (\count($theClass->getDependencies()) <= $this->max) {
            return;
        }

        $violations->add(Violation::create(
            $theClass->getFQCN(),
            ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
            $theClass->getFilePath()
        ));
    }
}
```

## Using it in your config

A custom expression is used like any built-in — pass an instance to `that()` or `should()`:

```php
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
    ->should(new HaveAtMostDependencies(10))
    ->because('domain classes with too many dependencies are doing too much');
```

Because `that()` accepts any `Expression`, you can equally use a custom expression as a **selector** — for example to define a cross-cutting "component" that is not a single namespace (every class that `IsA('App\Domain\DomainEvent')`, wherever it lives), and then constrain it with `should()`.

## Testing a custom rule

Treat it like any other unit: parse a small fixture into a `ClassDescription`, run `evaluate()`, and assert on the collected `Violations` — cover the passing case, the failing case (assert the message), and any edge cases your `appliesTo()` is meant to skip. See `tests/Unit/Expressions/` for the pattern used by the built-in rules.
