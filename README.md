# PHPArkitect 2.0 — the `v2-poc1` branch

Architecture rules for PHP: which classes may depend on what, who must
be final, where a name belongs. They are written in PHP, run in CI, and a
rule that is broken is reported at the `file:line` that breaks it.

This branch is a full rewrite. Every line in `src/` is new, nothing from
`main` is kept or run beside it, and the result is a proof of concept with
a defined end rather than a release. This file is for whoever opens the
branch: what runs, what is where, and where the rest is written down.

## What 2.0 does differently

- **Every class is parsed, `vendor/` included.** Parsed and not checked:
  rules judge the project's own code, but a question about inheritance can
  be answered through a dependency — a class extending a framework base
  class, an interface arriving two hops up — because the classes it runs
  through are in the parsed set.
- **`that()` and `should()` hold two different things.** A selector
  decides which classes a rule is about; a constraint decides what those
  classes must satisfy and reports one violation per offence. 1.x had one
  type in both positions, so `that(new DependOnlyOnTheseNamespaces(...))`
  was accepted and could not mean anything.
- **Nothing is autoloaded.** Inheritance is answered over the parsed set,
  not with `is_a()` or reflection — the one exception being whether a name
  is PHP's own, which parsed code cannot answer even in principle. So
  there is no bootstrap to configure, the answer does not depend on what
  happens to be loadable on the machine, and a chain that cannot be
  resolved says `Unknown` rather than guessing `false`.
- **The baseline survives rewording.** An entry is keyed by the class, the
  constraint and the constraint's own key — never by the line, which moves
  whenever anything above it does, and never by the message, which is
  prose we may reword. Occurrences are counted, so a second forbidden
  reference in an already-accepted class is still reported.
- **The components do not know about each other.** Parsing knows nothing
  of the graph, the graph nothing of the rules, and `run.php` enforces
  that. It is what leaves room on the outside: a cache is a decorator on
  the `ProjectParser` port when it arrives, and further analysis goes
  where the graph already is — a second pass over the parsed set rather
  than more work inside the parser.
- **Immutable wherever it can hold.** The parser above all: stateless per
  class, which is what fixes 1.x's one shared mutable builder, where a
  top-level function declared before a class leaked its parameter's
  dependency into that class. A constraint returns an `Outcome` instead of
  filling a `Violations` handed to it, which is what lets that collection
  be immutable too, and a `Config` is built with `with`-prefixed setters
  that return a new one.
- **A rule that protects nothing says so.** A `that()` that matched no
  class, an `except()` naming a class nobody answers to, and a `should()`
  that could not mean anything for the classes selected are each reported
  on their own, because each one is fixed somewhere else.

Gone: the PHPUnit integration; the `json` format, which nested by class
and omitted the file path, leaving `--format=gitlab` as the
machine-readable one; the closure-config form and the
`Architecture`/`RuleBuilders` DSL, so a config has one shape; `ClassSet`,
replaced by `ResideInDirectory` and `NotResideInDirectory`; both of 1.x's
ways to pass a baseline path on the command line, since the config and the
conventional name already cover it; and `--stop-on-failure`, which a
report that prints everything has nothing to do with.

What carried over is the knowledge of which PHP-syntax edge cases must not
regress: six years of them, as `tests/Parser/EdgeCasesFromV1Test.php`,
each case either matching 1.x or asserting where 2.0 diverges and why.

## Where to read what

- `docs/philosophy.md` — the principles the tool is judged against. The
  place to start when you are about to decide something.
- `ARCHITECTURE.md` — the working record of this branch: the decisions,
  the reasons that are not recoverable from the code, and the traps that
  cost time to find. Check it before re-opening a question. It dissolves
  into `docs/` and issues when the branch merges.
- `CLAUDE.md` — the conventions that outlive it: naming, types over
  annotations, how messages are worded.

## Running it

```
composer install
make test    # vendor/bin/phpunit tests
make lint    # php-cs-fixer, dry run
make fix     # php-cs-fixer
make run     # arkitect against its own architecture
make all
```

PHP 8.1 or newer, per `composer.json`; CI runs 8.1 through 8.5. There is
no `phpunit.xml`, so the suite path has to be passed — which is what the
Makefile is for.

## A config, and what a run prints

A config file is PHP that returns a `Config`:

```php
<?php
use Arkitect\Config;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Selector;

return Config::create(__DIR__)->add([
    Rule::allClasses()
        ->that(new Selector\ResideInNamespace('App\Domain'))
        ->should(new Constraint\NotDependOnTheseNamespaces(['App\Infra']))
        ->because('the domain does not know how it is stored'),
]);
```

```
$ vendor/bin/arkitect
✗ the domain does not know how it is stored
    src/Domain/Order.php:4  depends on App\Infra\Db

2 classes, 1 rule, 1 violation in 1 rule
```

A clean run is one line: `✓ 126 classes, 7 rules, no violations`.

`Config` is immutable and its setters say so: `withBaseline()`,
`withTargetPhpVersion()`, `withDependencies()` return a new config, so one
whose result is dropped has lost it. Everything under the root is parsed;
the dependency directories (`vendor` unless the project says otherwise)
are parsed and not checked. `targetPhpVersion()` is the version the
*analysed* project targets, not the one running arkitect.

## The CLI

```
arkitect [command] [options]

  check               report violations (the default)
  generate-baseline   accept every violation there is now
  prune-baseline      drop entries that no longer match anything

  --config=PATH       where the config is (default: phparkitect.php)
  --format=NAME       text (the default) or gitlab
  --skip-baseline     ignore the baseline, default one included
  --help              this
```

Exit codes: `0` clean, `1` violations, `2` misused — a bad option, a
missing config, a config that returns something other than a `Config`.

## What `src/` holds

Three components in a pipeline, and the pieces around them:

- `Parser/` — source to `ParsedClass`, one file at a time, with zero
  runtime calls, so the output is identical everywhere and cacheable.
  `php-parser` is named nowhere else.
- `Resolve/` — `ClassGraph`, the inheritance questions rules ask, answered
  from the parsed set. Reflection appears only in `InternalClasses`, for
  "is this name PHP's own".
- `Evaluate/` — the rules. `Selector/` decides which classes a rule is
  about, `Constraint/` what those classes must satisfy, `Rule` holds the
  two plus the reason a report prints.
- `Command/` — `Check`, `GenerateBaseline`, `PruneBaseline`: a `Config`
  in, a result out.
- `Report/` — how results leave: `TextReport`, `GitlabReport`.
- `Cli/` — `Console`, the composition root: a terminal in, an exit code
  out, no decision of its own.
- `FileSystem/` — the adapters behind `FileRepository` and
  `BaselineRepository`; the in-memory ones live in `tests/`.
- `Config`, `Baseline`, `DependencyDirectories`, `ProjectParser` at the
  root — what a run is about.

A rule answers more than pass or fail, and this is the part most likely to
surprise you: besides violations it reports the classes it could not
decide about and the classes a constraint cannot mean anything for.
"Could not determine" is never a violation and never silent, and checking
nothing has to look different from finding nothing.

## Writing rules

```
Rule::allClasses()->except(...)->that(...)->andThat(...)
    ->should(...)->because(...)
```

`because()` is the only way out of a half-written rule, and an empty
reason is rejected. `except()` takes whole class names, only before
`that()`, and a name no class answers to is reported rather than silently
excluding nothing.

Selectors, in `Evaluate\Selector`: `ResideInNamespace`,
`NotResideInNamespace`, `ResideInOneOfTheseNamespaces`,
`ResideInDirectory`, `NotResideInDirectory`, `HaveNameMatching`, `IsA`,
`Extend`, `Implement`. Several are combined with and.

Constraints, in `Evaluate\Constraint`: `NotDependOnTheseNamespaces`,
`NotDependOnTheseClasses`, `DependOnlyOnTheseNamespaces`,
`ResideInNamespace`, `HaveNameMatching`, `IsA`, `Extend`, `Implement`,
`IsFinal`, `IsAbstract`, `IsReadonly`. `Extend` and `Implement` take a
`Depth`: `Transitive` by default, since a class that inherits an interface
from its parent really does implement it, and `Direct` for a rule about
the declaration itself.

Three things to know about the arguments:

- Class names are fully qualified and carry no leading separator. A `\` in
  front is normalized away rather than rejected, because it is what people
  write when copying from code.
- `*` stands for part of one segment and never crosses a separator; `**`,
  written as a whole segment, stands for any number of segments. Nothing
  else is a wildcard, and an invalid pattern fails when constructed.
- A namespace pattern selects what is declared beneath it, so the class
  `App\Domain` is not in the namespace `App\Domain`. A directory pattern
  names a directory and never a file.

Multi-value constructors in the classes a config writes take an `array`,
not a splat, so a second argument stays possible. Internal collections use
typed variadics instead.

## The baseline

`generate-baseline` accepts every violation there is now, writing
`phparkitect-baseline.json`; a `check` with no baseline declared uses that
file if it is there. `prune-baseline` drops entries that no longer match.
An entry is keyed by the class, the constraint and the constraint's own
key — never by the line, which moves, and never by the message, which is
prose. Occurrences are counted, so a second forbidden reference in an
already-accepted class is still reported.

## `run.php`

Not the CLI — `bin/arkitect` is. It is throwaway wiring that runs arkitect
against its own architecture, and `make run` is where the boundaries
actually hold: the stage ordering (parsing knows nothing of resolving,
resolving nothing of evaluating), that nothing under `Evaluate\Selector`
touches `Violation`, and the two hexagon seams — no knowledge of which
library reads PHP source, none of who prints results.

Add a component, add its boundary there. Then invert the rule once and
watch it fail: a green rule proves nothing until it has been seen to bite.

## Where things stand

In `ARCHITECTURE.md`: `## PoC exit criteria` for what the PoC had to show,
`## Open` for what is deliberately not decided, and `## Left before 2.0`
for the surface that is missing.
