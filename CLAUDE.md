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
- A name must not promise more than it does. `ResideInPath` took a path
  and meant a directory, so a file path looked like a valid argument and
  silently selected nothing; `json` would have promised a
  machine-readable format and delivered one only GitLab reads. When a
  name cannot be made honest, that is the signal the thing behind it is
  wrong.
- `-er` names are avoided, with one settled exception: the `Parser` family
  (`ClassParser`, `RepositoryParser`, `ProjectParser`).
- v1's names carry over only when they still fit. `Expression` became
  `Constraint`; `ResideInOneOfTheseNamespaces` stayed.

## Messages name nothing the user cannot see

The system is the arkitect executable. The configuration and the analysed
code are outside it — the user wrote both, so naming them is not a leak.
The rest is a box nobody has opened, and a message naming its inside asks
the reader to picture something they have no picture of.

The line is not where it first looks: `add withBaseline() to say where it
goes` names a method in this source and is right, because the
configuration language is the user's, while *judged* belongs to
`RuleResult` and nobody writes it.

Two ways it breaks, both caught in review rather than in the writing:

- Saying how the tool found out instead of what it found. "3 baseline
  entries matched nothing" is the mechanism; "3 violations in the baseline
  look fixed" is what there is to act on.
- Reaching for the tool's own verb when a message has to name a mechanism
  at all. The fix is not a plainer synonym but the part of the config the
  reader wrote: a rule that could judge none of its classes is a
  `should()` that cannot mean anything for them.

## Clearer beats shorter

Nobody should have to learn this tool to use it, and explicit beats
implicit, so where a shorter message and a clearer one disagree the
clearer one wins. A suggested command is printed whole, as the reader
would type it — `argv[0]` and the options this run needed — not as a
subcommand they have to reassemble.

Not a licence to pad. The words have to buy comprehension: a message made
longer for symmetry with another one, or to mention what the reader did
not ask about, is only longer.

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
