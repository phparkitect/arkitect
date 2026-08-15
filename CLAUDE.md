# arkitect — working notes

## This branch

`v2-poc1` is a full rewrite: everything in `src/` is new, nothing from
`main` is kept or run alongside. `ARCHITECTURE.md` is the working record of
what was decided and why — a record, not a spec, and it dissolves into
`docs/` and issues when the branch merges. Treat it as the place to check
before re-opening a decision, and keep it honest: prose that dates itself
("this replaces X", "we rejected Y") is what rots when the design moves.

`run.php` is not the CLI. It is throwaway wiring that runs arkitect against
its own architecture, and it is where the hexagon boundaries are actually
enforced.

## Commands

`make test`, `make lint`, `make fix`, `make run`, `make all`.

## Naming

- Namespaces are singular even when they hold many things: `Constraint/`,
  `Selector/`, `Command/`.
- Plain names beat borrowed jargon. `ClassGraph`, not `Symbols`;
  `InternalClasses`, not `PhpCoreClasses` — and the second one is also
  named for what it actually tests, `ReflectionClass::isInternal()`.
- `-er` names are avoided, with one settled exception: the `Parser` family
  (`ClassParser`, `RepositoryParser`, `ProjectParser`).
- v1's names carry over only when they still fit. `Expression` became
  `Constraint`; `ResideInOneOfTheseNamespaces` stayed.

## Types over annotations

A `@param list<X>` is a promise to an analyser; PHP checks nothing. Where
the language can hold the type, use it — typed variadic collections
(`Violations`, `Rules`, `Selectors`) exist for that, and each one replaced
a hand-written `instanceof` loop. What is left in phpdoc is what PHP cannot
express: private state behind an already-typed constructor, and lists of
scalars.

The same applies to invariants: prefer making an invalid state
unconstructible over validating it. A required root is a constructor
argument, not a builder step; `Violation`'s constructor is private because
its location comes from an already-valid object.

## Before imposing a rule, check it against real code

Validation rules are verified against this repo's own `vendor/` (~2,600
classes, ~26,000 type references) before being enforced. Two rules that
looked obviously right rejected thousands of perfectly valid names on the
first run.

Same for architecture rules: a green rule proves nothing until it has been
inverted and seen to fail.
