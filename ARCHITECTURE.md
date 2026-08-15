# PHPArkitect 2.0 — architecture PoC

This document is a working record for the `v2-poc1` branch, not a spec. It
exists to keep decisions from being re-litigated every session. When this
branch merges, its content dissolves into `docs/` and into issues — this
file itself should not survive the merge.

Guiding reference: `docs/philosophy.md`, already merged on `main`. Every
decision below cites which principle it serves when it isn't obvious.

## Scope: this is a full rewrite

Every line in `src/` is written fresh. Nothing from `main` is kept,
patched, or run side-by-side as a fallback — not the analyzer, not
`Expression`/`Rules`/`CLI`. There is no intermediate state where old and new
implementations coexist in this codebase.

The one thing that crosses over from `main` is not code: it's *knowledge*
of which PHP-syntax edge cases must not regress, currently encoded in
`tests/Unit/Analyzer/FileParser/CanParseClassTest.php` and the E2E fixtures
— six years of "someone's real code broke the parser." That knowledge is
extracted into a checklist (a spec, read-only reference) before any new test
is written. The new tests themselves are written from scratch against the
new API, driven by that checklist — no old test file is copied, adapted, or
kept runnable.

## Evidence gathered before writing code

- **State leak, reproduced on `main`**: `ClassDescriptionBuilder` is one
  mutable instance shared across the whole traversal. A top-level function
  declared before a class leaks its parameter's dependency into that class:

  ```php
  namespace App;
  use App\Infra\Alpha;
  function helper(Alpha $a): void {}
  class Innocent {}
  ```
  `Innocent` is reported as depending on `Alpha`. It shouldn't depend on
  anything. This is a correctness bug today, independent of 2.0 — the fix
  here is to make the parse step stateless per class, not to route around it.

- **`vendor` parsing is cheap**, benchmarked against this repo's own
  `vendor/` (2,515 files, 20MB) with the current parser:
  - cold parse: 5.90s (427 files/s), peak memory 52MB
  - cache serialized: 24.5MB (0.9MB gzipped)
  - warm (unserialize): 0.19s — **~3% of cold**
  - cache validation (read+hash every file): 0.05s — negligible, no need for
    mtime-based shortcuts
  - Open risk, not yet measured: memory footprint holding tens of thousands
    of `ClassDescription` in RAM at once on a large real project (vendor
    trees of 15–30k files are common). Time is not expected to be the
    problem; memory might be.

## Priority order: reliable and comfortable before fast

Performance work is explicitly out of scope for this PoC. The vendor-parsing
numbers above are kept as evidence the approach isn't obviously too slow,
not as a target — no throughput or memory number gates this PoC. Per
*Reliable first, fast second* in the philosophy: get a tool that is correct
and pleasant to use first, then make it fast. The one deliberate exception
is the internal-class check (see stage 1) — reflection is acceptable there
specifically. It is now implemented, as `Resolve\InternalClasses`.

## The four stages

### 1. Parse — pure, per file, cacheable

```
(file content, target-php-version, annotation flag) → ParsedClass
```

A `ParsedClass` declares what the file says about itself — FQCN and its
own declaration line, unresolved `extends`/`implements`/`use trait` names,
type references, attributes, docblocks — all with line numbers. The class's
own declaration line matters as much as the others: it's what gives a
purely structural check (`IsFinal`, `HaveNameMatching`, no specific
referenced node) something to point at — see `Violation` below.

It records the *type*, not the spelling, where the two disagree: an enum
carries `isFinal: true` even though the keyword is a syntax error on an
enum. This is still language knowledge and needs no runtime call, and the
alternative is worse — recording `false` would make every "must be final"
rule report an enum the user cannot fix. The same "record the value, not the
syntax" principle governs names: see *Names: one spelling* below.

**Hard constraint on this stage: zero runtime calls.** No `class_exists`,
no `ReflectionClass`, no `is_a()`. This is what today's
`ClassDescriptionBuilder::isPhpCoreClass()` violates — it makes parse output
depend on which PHP extensions happen to be loaded on the machine running
it, which is exactly the kind of "cache that's occasionally wrong" the
philosophy rules out (*Reliable first, fast second*). That filter moves out
of parsing entirely: it is `Resolve\InternalClasses` (stage 2, not cached),
the one deliberate, scoped exception to "no runtime calls". It is named for
what it actually tests — `ReflectionClass::isInternal()`, which is true for
extension classes like `PDO` too, not only for PHP's core.

Runs over `vendor/` through the identical pipeline — no special-casing.
This is needed for inheritance resolution to be correct at all: a project
class extending a vendor class needs the vendor class's own ancestor chain,
which only exists if vendor was parsed too (see stage 2). Caching is a
secondary benefit that falls out of this, not the reason for it — vendor
doesn't change between runs, so its cache hit rate is effectively 100%
after the first run.

**Cache key**: file path + content hash + target-php-version + annotation
flag + arkitect version. (Not mtime — validation cost is negligible even at
2,500 files, see above.)

### 2. Resolve — a graph, not a stage

Named a stage here because that was the map before the code existed. There
is no class that "resolves": there is a `ClassGraph` and the act of
building one, which `Codebase` does. Worth knowing when reading the four
stages below as though they were four components — three of them are.

In-memory, per run, not cached (and doesn't need to be).

```
all ParsedClass (project + vendor) → ClassGraph → resolved membership
```

Turns names into links: for each class, the transitive ancestor chain
through `extends`/`implements`/traits, including ancestors that live in
`vendor`. If an ancestor can't be found anywhere in the parsed set, the
graph says so explicitly — it does not silently resolve to `false`, which is
what `IsA`'s current `is_a($fqcn, $allowed, true)` does when the class isn't
autoloadable.

**Internal classes are the exception, and they had to be.** A class PHP
compiled in — `RuntimeException`, `ArrayObject`, but equally `PDO` — has no
source file, so it can never be in the parsed set however much is parsed.
Treating that dead end as unknown made every descendant of one
unanswerable: `App\MyEx extends \RuntimeException` came back `Unknown` for
*every* target, and that is every custom exception in every project, not an
edge case. So `ClassGraph` consults `InternalClasses` when a name isn't in
the parsed set:

- internal ancestor, user-defined target → `No`, definitively. An internal
  class only ever inherits from other internal ones, since no C extension
  can name a user-defined type, so the target is unreachable through it.
  This is an inference, not a runtime lookup.
- internal ancestor, internal target → ask the runtime. Deterministic, and
  the same answer on any machine with the same extensions loaded.
- anything else → `Unknown`, which now means only what it should: this name
  has source somewhere and it wasn't in what we parsed (a missing
  `vendor/`, an excluded path).

This is the stage that replaces the reflection-based approach `#582`
originally proposed: `IsA`/`Implement`/`Extend` are graph queries over
parsed data, not runtime calls (`#169`). Reflection as the *answer* was
rejected because it reintroduces a runtime dependency and isn't reliably
cacheable — the problem being removed from the parse stage. What survives
of it is the narrow question above, "is this name internal", which only
exists because an internal class has no source to parse and so can never
be answered from parsed data at all.

Cheap enough not to need caching: linking, not parsing — no file I/O, no
AST.

Known edge cases not designed for yet — duplicate FQCNs (e.g. polyfills
defining the same class in more than one file), inheritance cycles, trait
conflicts, diamond interfaces: deliberately not speculatively designed for.
Handle each when a concrete case surfaces it, see Open.

### 3. Evaluate — two contracts, not one

```php
namespace Arkitect\Evaluate\Selector;

interface Selector      // what the rule is about — that()
{
    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection;
}

namespace Arkitect\Evaluate\Constraint;

interface Constraint    // what it requires — should(); was void, mutated a param — #670
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome;
}
```

`Expression` is gone, and so is `appliesTo()` — see "`appliesTo()` resolved
by decomposition" below, which is what the two contracts are for.

`ClassGraph` is a parameter rather than a constructor dependency: the
config builds constraints before anything has been parsed, so the graph
doesn't exist yet when they are constructed.

Selectors and constraints are separate *classes*, not one class
implementing both interfaces, even where they share a name
(`ResideInNamespace` exists in both namespaces). Two reasons, both load
bearing:

- A constraint is strictly richer than a predicate.
  `DependOnlyOnTheseNamespaces` reports one violation per offending
  dependency, each on its own line; no boolean expresses that, so it can
  never be a selector. The type system now rejects
  `that(new DependOnlyOnTheseNamespaces(...))`, which v1 accepted and did
  something incoherent with.
- The same question gets different answers in the two positions. An
  unresolvable ancestor chain is recorded per class by a constraint; for a
  selector it decides nothing at all, so `matches()` returns a `Selection`
  rather than a bool. One shared class would have forced those to be the
  same answer.

The duplicated names cost nothing in practice: both `ResideInNamespace`
classes delegate to the same `Pattern` value object, so the logic lives in
one place. And nothing under `Evaluate\Selector` references `Violation` —
checked as a rule in `run.php`, not left as an intention.

**"I couldn't tell" is not a violation, and gets its own channel.** A
violation claims the code breaks a rule; an unresolvable ancestor chain
says the tool couldn't decide, because our input was incomplete. Folding
the second into the first had a concrete cost: the baseline keys on
violations, so a resolution failure could be accepted once and then hidden
forever, freezing a broken parse scope into the project's accepted state.

So `Constraint::evaluate()` returns an `Outcome` (violations *and*
unresolved classes), `Selector::matches()` returns `Selection::Unresolved`
when it can't tell, and `RuleResult` carries both — `isConclusive()` is
false when anything went unresolved, whatever the violation count.

It is an input error in the same family as `ParsingError`, and that is
exactly why it is collected rather than thrown: a syntax error in one file
doesn't cost the report on the other five hundred, and an undecidable
class shouldn't either. After the stage 2 fix for internal classes this
should be rare, and when it happens it means one thing — something with
PHP source wasn't in what we parsed.

Direct vs. transitive is a `Depth` parameter on `Implement`/`Extend`, not
a second pair of classes: the twin-class route is the wart `#516` already
flags, and it multiplies as soon as negations arrive. `Depth::Transitive`
is the default because the alternative is a correctness trap — a class
inheriting an interface from its parent really does implement it.

Consequences that fall out of this, not separate work:
- `Violation` identity becomes structured data (fqcn, filePath, line,
  constraint, detail) instead of a rendered sentence parsed back with
  `', but '`/`' because '` string matching — the baseline keys on data, not
  prose (`#671`). Unlike today (`Violation::$line` is nullable, populated
  only by dependency-type checks via `ClassDependency`), `line` is never
  null, and there are two named constructors for the two cases:
  `Violation::createAt()` takes the `TypeReference` and points at that
  node's line, `Violation::create()` falls back to the class's own
  declaration line, which `ParsedClass` carries (see stage 1). This is what
  actually backs the Report requirement below that every violation carries
  `file:line` — without it, that requirement had nothing guaranteeing it
  for roughly half of today's expressions.
- `Violations` can become immutable — previously blocked specifically by
  `evaluate()` requiring a mutable accumulator parameter.
- Multi-value constructors take `array`, never splat (`#599`) — **in the
  classes a user writes in a config**, which is where the inconsistency
  `#599` reports actually lives: v1's `src/Expression/ForClasses/` has
  `Extend(string ...$classNames)` next to `MatchOneOfTheseNames(array
  $names)`. The technical half of the reason points the same way — the two
  v1 expressions carrying a second argument (`$exclude`) are precisely the
  ones taking an array, since a variadic must come last. v2 hit it again:
  `Implement(string $target, Depth $depth)` could not have gained its
  second argument as a variadic.

  Internal collections were never in scope, and use typed variadics —
  `Violations`, `TypeReferences`, `ClassGraph`. Nobody constructs those
  from a config, so there is no API to keep consistent, and the variadic
  buys something an `array` cannot: PHP checks the element type itself,
  instead of a `list<X>` docblock and a hand-written loop that can drift
  from it.

Explicitly **not** part of this rewrite: `Not`/`And`/`Or` composable
decorators to replace the 17 `Not*`/`IsNot*` twin classes
(`#516`/`#394`/`#395`/`#387`). A returning `evaluate()` would make them
technically possible, but that's not enough reason on its own — it isn't
clearly a DX win over the current twin classes, which cause no confusion in
practice. Left out for now; revisit separately if a real need shows up.

**`appliesTo()` resolved by decomposition.** It was never one problem: it
meant "excluded from the selector" in `that()` and "constraint vacuously
holds" in `should()` because a single type occupied both positions and the
method had to work out at runtime which one it was in. Giving the two
positions two types removes the ambiguity rather than settling it, so the
method isn't built at all.

That leaves the two halves it was conflating, each answered separately:

- **Scope.** Handled by `Selector`, and the report requirement that
  "matched nothing" be visibly different from "matched everything and found
  no violations" is met by `RuleResult::$checked` — the count of classes the
  rule actually looked at. Both cases produce zero violations; only the
  count separates them, so it belongs to whoever runs the rule, not to a
  method on the checks themselves.
- **Applicability.** `IsFinal` on an interface is not a violation the user
  can fix, because an interface *cannot* be final — PHP rejects the keyword
  outright, as it does on traits and enums, and on abstract classes.

  That last one matters: applicability is **not** determined by `ClassKind`
  alone. An abstract class is an ordinary class and still cannot be final,
  so any design keyed on kinds would have looked complete and quietly
  missed it. The honest boundary is *who decides*: the language, or the
  user. "An interface cannot be final" is a fact about PHP; "classes in
  `App\Legacy` are exempt" is intent, and belongs in a selector. Kind and
  modifiers are both language facts, so the constraint — which knows both,
  and knows why its requirement is impossible — is what says so, through a
  third `Outcome` channel.

  It is carried, not printed. Someone who writes "domain objects must be
  final" means the classes, and is not surprised the interface beside them
  was skipped; a count on every run would be noise about something they
  cannot act on, and noise teaches people to ignore output. `RuleResult`
  keeps the data so a report or a `--verbose` can use it, and surfaces it in
  the one case where silence would mislead: `judgedNothing()`, a rule that
  matched classes and could judge none of them. That is the same reasoning
  as `matchedNothing()`, and deliberately a separate signal, because one is
  fixed in the `that()` and the other in the `should()`.

### 4. Config and Report — the two public faces

**Config** is a fluent object, not a closure that mutates a parameter:

```php
return Config::create(__DIR__)
    ->add($rules);
```

The root is a constructor argument rather than a fluent step, because it is
required and PHP already refuses to build an object without a required
argument — a builder to enforce that would be an elaborate way to restate
it. `Rule` needs one and this does not: there, four steps deep, `because()`
is genuinely forgettable. The rule that falls out and governs what comes
next (`targetPhpVersion()`, the baseline path): **mandatory goes in the
constructor, optional is fluent**, so a config file shows at a glance what
has to be given.

The root is never inferred from the working directory or from where the
config file sits, because a run has to mean the same thing wherever it was
started from.

**Parse scope and check scope are not the same scope**, which is the answer
to the question that sat in Open until `root()` existed to force it. `vendor/`
*must* be parsed — inheritance cannot be resolved otherwise. It *must not* be
checked, or a config that forgets a namespace selector reports thousands of
violations in code its author cannot change, which is exactly the kind of
trap namespace filtering alone would leave in place. So everything under the
root is parsed, and everything except `vendor/` is checked.

`Codebase` is where that lives: one parse, two views of it — `ownClasses`,
what rules may judge, and `graph`, what names resolve against. Not in
`Config`, because nobody declared it; it is our policy, and it moves to
`Config` the day a project can override it. Against this repo the two views
are 83 classes and 2703.

Nothing downstream had to change to accommodate this: `Rule::check(array
$classes, ClassGraph $graph)` already took the classes to judge and the
graph to resolve against as separate arguments, for an unrelated reason (the
graph cannot be a constructor dependency of a constraint, since the config
is read before anything is parsed).

No `ClassSet`. Its job — pairing "what to scan" with "which rules apply" —
is split between the root-scan (stage 1), the `vendor/` line drawn by
`Codebase`, and per-rule `that()` selectors; keeping it would be a second
filtering vocabulary for the same intent.

This paragraph used to claim the root-scan plus `that()` selectors were
enough on their own. They are not, and finding out is what settled the
parse-scope question above: without `Codebase` drawing the line, a rule
that forgets its selector checks `vendor/`. Selectors narrow what a rule
is about; they are not a substitute for knowing whose code it is.

Beyond that, filtering is namespace-based
(`Selector\ResideInOneOfTheseNamespaces`), which covers the near-total
majority of cases, or, for the rare case where directory and namespace
disagree (generated code, monorepo packages, legacy trees), a new
filesystem-based selector:

```php
Rule::allClasses()
    ->that(new Selector\ResideInPath('packages/legacy'))
    ->should(...)
    ->because('legacy code still needs the old constraints');
```

The closure form is removed, not deprecated-and-kept: accepting both would
mean every tutorial and every Stack Overflow answer shows a different shape
(*one way to do each thing*). It also removes the specific footgun where a
`phparkitect.php` runs, builds `$rules`, and forgets to call `$config->add()`
— today that's a silent green, because nothing signals that rules exist but
were never registered.

Config becomes the source of truth for project properties per `#650`:
`target-php-version` and baseline path move out of CLI flags into the
config file; the CLI keeps only bootstrap (`--config`, `--autoload`) and
genuine one-off overrides (`--format`, `--stop-on-failure`,
`--skip-baseline`).

`targetPhpVersion()` is optional, not required for a working config. When
unset, it defaults to the running interpreter's own version — this already
matches today's de facto behavior (`Config`'s own field defaults to
`TargetPhpVersion::latest()`, but `CheckHandler` unconditionally overwrites
it with `TargetPhpVersion::create($cliOption)`, which falls back to
`\PHP_VERSION` whenever the CLI flag isn't passed; today's default is an
accident of two files disagreeing, not a decision). The one honest
trade-off: the same `phparkitect.php` can parse differently depending on
which PHP happens to run it, which matters specifically for a project whose
minimum supported PHP is older than its CI/dev interpreter — that's exactly
the case `#650` says should pin `targetPhpVersion()` explicitly rather than
rely on the default.

The `Architecture`/`RuleBuilders` DSL is dropped, not carried over — this
was already the deprecation direction pre-2.0, gated on the philosophy doc
that's now merged.

Open question worth tracking: with a single `root()`, everything under it
gets *parsed*, but `that()` namespace filters decide what rules *check* —
so a config with no namespace filter now implicitly checks `vendor/` too,
which no config does today. If namespace filtering alone doesn't turn out
to be enough in practice, the alternative is splitting the concept in two —
what gets parsed (always everything, for resolution) vs. what gets checked
by default (typically `src/` + `tests/`) — instead of relying purely on
per-rule `that()` filters. Not decided; needs a concrete case to settle it.

**Migration policy**: 2.0 is a hard break, not a smooth one. There is no
compatibility shim for third-party expression implementations and no
soft-deprecation window within 2.0 itself — the contract changes
(`Expression` split into `Selector` and `Constraint`, `evaluate()`'s
signature) are exactly the kind of thing a shim can't paper over cleanly.
v1 is expected to keep being maintained in parallel for users who aren't
ready to move, rather than 2.0 trying to ease everyone across at once.

**Report** is rewritten from scratch, not ported — unlike the analyzer, it
holds no accumulated bug-fix knowledge worth preserving. Requirements: every
violation line carries `file:line`; violations group by rule, using the
rule's `because()` text as the heading (no new DSL clause needed — the
name was already there); a rule that matches zero classes, or a config that
registers zero rules, must not report `✅ No violations detected` — checking
nothing and finding nothing must be visibly different outcomes (*Reliable
first, fast second*: a result you can't trust is worse than one that makes
you wait).

Built as `Report\TextReport`, and designed around *when* the output is read:
in CI only when it fails, and often only the last lines; locally over and
over while fixing; and, on first adoption, as a wall of hundreds of
violations. So the failing run is what the format is for, and a clean run
is a single line.

```
✗ selectors should not know how parsing works
    Evaluate/Selector/Extend.php:22   depends on Arkitect\Parser\Fqcn
    Evaluate/Selector/IsA.php:21      depends on Arkitect\Parser\Fqcn

! could not check 1 class
    src/Api/Controller.php:10   cannot be checked against App\Contract: …
  vendor/ is probably outside the analysed root

! "a rule about a namespace that does not exist" matched no classes

49 classes, 4 rules, 9 violations in 1 rule
```

- **`file:line` leads every line**, because that is the part terminals and
  IDEs turn into a link — the single detail that most changes what using
  this feels like locally.
- **The class name is not repeated.** The first draft had it, and running
  the report against real code rather than a mock killed it immediately:
  with real paths and namespaces the FQCN was two thirds of the line and
  said only what `file:line` already said.
- **Rules that pass are not printed.** With a dozen rules, ten green lines
  hide the two red ones; the summary says how many ran. A `--verbose` can
  list them.
- **Deterministic order** (file, then line) so two runs over the same code
  produce identical bytes. The baseline will depend on that.
- **Everything is printed, never truncated** — hiding violations to keep
  the output short is the baseline's job, not the formatter's.
- **The three channels carry different weight.** Violations are the point;
  unresolved classes and unparsable files are setup problems, so they get
  their own sections and the unresolved one says what to do; not-applicable
  is silent except when a rule could judge nothing at all.

Exit codes: `0` clean, `1` for violations *and* for anything that went
unchecked (unresolved classes, unparsable files), `2` for a usage error
such as a root that isn't a directory. One code for both failure kinds on
purpose: a run that couldn't check part of the project has not passed, and
splitting the two would be a distinction CI can't act on differently.

File paths are relative to the scanned root, which is why `root()` being
the project root matters: scanning `src/` directly yields
`Evaluate/Selector/Extend.php`, a path that does not resolve from the
project root and therefore is not clickable. With `Config`'s root that
fixes itself by construction, and it was the first concrete evidence that
`root()` had consequences beyond configuration tidiness.

## Decided

- Performance is out of scope for this PoC. Correctness and DX come first;
  optimize only once those are solid.
- Parse is pure and cacheable. The internal-class check is the one
  deliberate exception to "no runtime calls" — it uses reflection, and it
  lives in resolve (`Resolve\InternalClasses`), never in parse. It keeps
  autoloading off: internal symbols are present without it, and letting the
  autoloader run would execute project code just to answer a name lookup.
- `vendor/` is parsed through the same pipeline as project code — primarily
  because inheritance resolution needs it, not primarily for caching.
- Inheritance resolution is a graph built at resolve time, over parsed
  data only — with one scoped exception, added once it turned out to be
  unavoidable: internal classes have no source to parse, so `ClassGraph`
  asks `InternalClasses` (and, only when both ends are internal, the
  runtime) rather than reporting `Unknown` for every descendant of an
  exception. Resolve is not cached, so this does not reintroduce the
  cacheability problem that ruled reflection out of parsing.
- Class names are fully qualified with no leading separator, everywhere.
  `Fqcn` enforces it and normalizes `\App\Foo` to `App\Foo` rather than
  rejecting it — the two name the same class, and the second is what
  php-parser, `Foo::class` and `ClassGraph`'s index all use. Values that
  aren't names at all are rejected outright, as is a line number below 1.
- "Could not determine" is never a violation. An unresolvable ancestor
  chain goes to its own channel (`Outcome::$unresolved`,
  `RuleResult::$unresolved`, `Selection::Unresolved`) because it is a gap in
  our input, not a fault in the code — and because a baseline keys on
  violations, so folding the two together would let a broken parse scope be
  accepted once and hidden from then on. Collected rather than thrown, for
  the same reason `ParsingError` is: one undecidable class must not cost the
  report on everything else.
- `Constraint::evaluate()` returns an `Outcome`. There is no `appliesTo()`:
  `Selector` and `Constraint` are separate contracts, which is what made
  the method unnecessary rather than merely hard to specify.
- Every `Violation` carries a line — never null. `ParsedClass` stores its
  own declaration line specifically so structural checks (no specific
  referenced node) have a fallback instead of reporting no line at all.
- A pattern means the same thing whether or not it contains a wildcard: the
  name itself, or anything beneath it. v1 gave wildcard patterns to
  `fnmatch` against the whole name and dropped the "beneath" half, so
  `App\*\Domain` silently matched nothing. Patterns are also validated when
  constructed, not halfway through a run.
- In `DependOnlyOnTheseNamespaces`, only the class's *own* namespace is
  implicitly allowed. v1 skipped a dependency whenever the class sat
  anywhere beneath the dependency's namespace, which silently permitted
  every parent namespace too.
- `DependOnlyOnTheseNamespaces` and `NotDependOnTheseNamespaces` are not
  negations of each other — one lists what is permitted and forbids the
  rest, the other lists what is forbidden and says nothing about the rest.
  Both exist on purpose; this is not the twin-class pattern below.
- `Config` is a fluent object; the closure-config form is removed, not kept
  as an alternative.
- `targetPhpVersion()` is optional, defaulting to the running interpreter's
  version — matches today's de facto behavior, made intentional.
- PHPArkitect 2.0 itself requires PHP `^8.5` to run (`composer.json`). This
  is independent of `TargetPhpVersion` (8.0–8.5), which is about what PHP
  version the *analyzed* project targets, not what runs the tool — same
  distinction PHPStan/Psalm/Rector make: a modern runtime for the tool,
  older syntax still analyzable. Not a decision to stop supporting
  8.0–8.4 projects; `TargetPhpVersion`'s range is unchanged. Unlocks writing
  the tool's own code with `readonly` and other 8.1+ syntax freely, which
  the parser component now does throughout its value objects.
- `ClassSet` is removed. A single `root()` replaces it; filtering is
  namespace-based (`that()`) or, exceptionally, path-based
  (`ResideInPath`).
- The root is explicit, single and required: a constructor argument, not a
  fluent step. Never inferred from cwd or from the config file's location.
  Mandatory settings go in the constructor, optional ones are fluent.
- Parsing and checking are separate scopes. Everything under the root is
  parsed so names can resolve; everything except `vendor/` is checked. The
  rule lives in `Codebase`, not `Config`, because it is our policy rather
  than the user's declaration.
- `Check` orchestrates: parse, split into a `Codebase`, one `check()` per
  rule, collect. It takes the parser injected and returns a `CheckResult`,
  knowing neither where files come from nor where results go. `isClean()`
  is on the result, since whether a run passed is not presentation.
- Comments explain why, when the code cannot. A phpdoc that describes a
  type is a promise to an analyser; prefer real code where PHP can hold it
  — `Violations`, `Rules`, `Selectors`, `RuleResults`, `ParsingErrors` are
  typed variadic collections for that reason, and each replaced a
  hand-written `instanceof` loop or a `list<X>` annotation. What is left
  is what PHP cannot express: private state behind an already-typed
  constructor, and lists of scalars.
- Report is rebuilt from scratch against the new structured `Violation`.
  Exit `1` covers both violations and anything left unchecked: a run that
  could not look at part of the project has not passed.
- 2.0 is a hard break for third-party expression authors — no
  compatibility shim. v1 keeps being maintained in parallel.
- `Not`/`And`/`Or` composable expressions are explicitly out of this
  rewrite — not clearly a DX improvement over the existing twin classes.

## Open — not yet decided, do not treat as settled

- **Multi-root / monorepo case**: parked. Addressed via `ResideInPath` when
  a concrete case shows it's needed, not by reintroducing multiple
  `ClassSet`-like roots. If `ResideInPath` turns out not to be enough in
  practice, this needs revisiting.
- **Default exclusions for root-scan** (`.git`, build/cache dirs,
  intentionally-broken parse-error fixtures like this repo's own
  `tests/E2E/_fixtures/parse_error/`): explicitly deferred by product
  decision — not a blocker for the PoC, revisit once it's a real problem
  rather than a hypothetical one.
- **Cache storage location and invalidation**: project-local directory vs.
  system cache dir; whether `vendor`'s cache invalidates as a block on
  `composer.lock` changes rather than per-file.
- **Resolve-graph edge cases** — duplicate FQCNs, inheritance cycles, trait
  conflicts, diamond interfaces: not designed for; handle each when a real
  case surfaces it rather than speculatively now.

## Parser component: design note

`ClassCollector` holds no instance state — every method is a pure function
of its arguments. Declarations and the facts inside them come from two
independent recursive walks: `findClasses` looks for named class-like
declarations; `collectDependencies`/`collectTraits`/`collectDocBlocks` look
for facts inside *one* declaration's own body, and are only ever called
with that body as their root. The state-leak bug (a top-level function's
parameter attaching to the next class, see the reproduction above) is
structurally impossible as a result, not guarded against: there is no code
path that hands a top-level function's body to the dependency walk.

A stateful alternative — a `NodeVisitorAbstract` with a stack of mutable
accumulators, scoped via push/pop on entering/leaving a declaration — also
fixes the leak, but by checking "is the stack empty" at every call site
rather than by construction: a guard that has to be remembered at each new
call site, not a state that can't exist. Rejected for that reason; don't
reintroduce it.

## Names: one spelling, enforced by `Fqcn`

Every class name in the codebase is fully qualified with no leading
separator. That isn't a style preference: `ClassGraph` indexes classes by
this exact string, so accepting `App\Foo` and `\App\Foo` both would
silently make them two unrelated types, and a rule about one would match
nothing.

The form is also not really a choice — it is what php-parser's `toString()`
returns, what `namespacedName->toCodeString()` gives for the class's own
name, and what `Foo::class` evaluates to, which is how people write names
in rules. The leading `\` belongs to source syntax (`extends \Vendor\Bar`),
not to the name as a value.

`Fqcn` holds that rule in one place and **normalizes rather than rejects** a
leading separator, since `\App\Foo` and `App\Foo` name the same class
beyond any ambiguity and the first is what people write when copying from
code. Exactly one separator is stripped, so `\\App\Foo` still fails: that
is not a name anyone meant.

This was found by writing the rule, not by reasoning about it.
`IsA('\App\Contract')` used to fail in the worst possible direction —
the target matched nothing stored, so every class in a codebase that
satisfied the rule was reported as violating it, while
`ResideInNamespace('\App\Domain')` merely selected nothing at all.

`Fqcn` is used by `TypeReference`, by `ParsedClass` (whose `shortName()`
and `namespaceName()` are derived from it rather than duplicating the
string arithmetic), and by every constraint and selector that takes a
target name. `Pattern` normalizes the same way without using it, since a
pattern carries wildcards and is not a class name.

`TypeReference` also requires `line >= 1`: php-parser answers `-1` when a
node has no position, and that value would otherwise travel intact into a
violation reported at `src/Foo.php:-1`. Both rules were checked against
real input before being imposed — 26,680 type references and 2,620 FQCNs
parsed out of this repo's own `vendor/` satisfy them, none with a line
below 1.

## Parser component: @throws resolution

`@throws` docblock tags are resolved to dependencies two ways: a
leading-`\` name is already fully qualified; a single-segment short name
resolves via the file's own `use` imports (built once per file by
`collectUseImports`, a fourth independent recursive walk following the
same shape as the others). A short name with no matching import is left
unresolved — deliberately not guessed as "probably the same namespace":
without redoing full namespace resolution there's no reliable way to tell
a same-namespace class from a typo, and getting it wrong silently is worse
than not extracting it, per *Explicit, never ambiguous*. Property hooks
(PHP 8.4) and `use function`/`use const` needed no special handling — the
existing per-node-kind dispatch and the generic recursion already do the
right thing, confirmed by tests rather than assumed.

## Ports and adapters, arrived at rather than adopted

`FileRepository` was pulled out first and on its own merits: testing the
parser meant real temp directories and `mkdir`/`chmod`/`rmdir` per test.
The note here used to say explicitly that this was *not* a general
hexagonal posture. It became one anyway, one seam at a time, each for a
reason of its own:

- **`FileRepository`** — so parsing is testable without disk.
  `FilesystemFileRepository` is the production adapter, and
  `FilesystemFileRepositoryTest` keeps a small suite against real I/O so
  the abstraction itself isn't unverified. `InMemoryFileRepository`
  (test-only) replaces temp directories everywhere else, and is what lets
  `CheckTest` run every stage together in memory.
- **`ProjectParser`** — the port `Check` depends on, with
  `Parser\RepositoryParser` reading a `FileRepository` and
  `Parser\ClassParser` turning one file's source into classes. The cache
  planned in stage 1 is a decorator on this interface; that is what the
  port is for, more than swappability nobody wants.
- **`ClassGraph`** — the questions rules ask (`isA`, `hasAncestor`)
  separated from `ParsedClassGraph`, the one way of answering them today.
  Not for a second implementation: the class reached for reflection
  through `InternalClasses`, so a runtime call was sitting in what was
  being called domain. Now it sits in an implementation.
- **`Report`** — how results leave. `TextReport` is written for a human at
  a terminal; a machine-readable format is a known requirement, which is
  what makes the interface more than speculation.

What this is *not*: a `Domain/` directory. The tree groups by component
(`Parser`, `Resolve`, `Evaluate`, `Report`), and layering it as well would
put two organizing principles in one tree while nesting every name a level
deeper. The boundary a `Domain/` would document is already *checked*: two
rules in `run.php` say that nothing under `Evaluate` knows which library
reads PHP source, or who prints results. A directory cannot enforce that;
a rule does.

`Command/` holds `Check` and `CheckResult`, singular like `Constraint/`
and `Selector/` beside it. `generate-baseline` (`#648`) and
`prune-baseline` (`#649`) are what will make it hold more than one.

## Resolve component: design note

`ClassGraph::isA()` is built and verified across the actual boundary that
justifies parsing `vendor/` at all: `tests/Resolve/ProjectAndVendorTest.php`
parses a small project-side fixture and nikic/php-parser's real source
into two separate `ParseResult`s, merges their classes into one `ClassGraph`,
and confirms both a direct and a transitive relationship that only resolve
correctly because the vendor side's own ancestor chain is visible too
(`App\MyVisitor extends NodeVisitorAbstract`, which itself `implements
NodeVisitor` — a real edge inside nikic/php-parser, not a fixture). Before
this, every other Resolve test built its `ClassGraph` from a single parsed
set (synthetic fixtures, or `vendor/` alone) — none of them actually
exercised combining project and vendor classes into one graph.

Settled since: `Implement`/`Extend` take a `Depth`, defaulting to
transitive, so both readings are available without a second pair of
classes. `Depth::Direct` reads the declaration and never consults the
graph, so it cannot return `Unknown`.

`Extend` transitive needed a second query rather than reusing `isA()`:
`ClassGraph::hasAncestor()` follows the `extends` chain only, and differs
from `isA()` in two ways that have tests of their own — an implemented
interface is a supertype but not an ancestor, and a class is a subtype of
itself but not its own ancestor. Without that distinction `Extend` would
just be a synonym for `IsA`. Both queries match a declared parent by name
before walking into it, so extending a class that was never parsed still
answers definitively instead of `Unknown`.

## Evaluate component: design note

`Rule` holds selectors, one constraint and a reason. `RuleResult` carries
`selected` and `checked` separately — they differ when a constraint could
not mean anything for some of the classes picked — alongside the
violations, the classes it couldn't decide about, and the ones it couldn't
judge. That is what it takes to answer "did this rule check anything"
(`matchedNothing()`, `judgedNothing()`) and "can the answer be trusted"
(`isConclusive()`).

Rules are written through the DSL, which is the shape v1 established and
2.0 keeps — with two changes. `Rule::allClasses()` returns a `RuleDraft`
rather than a half-built `Rule`, so the incomplete states have their own
types and `because()` is the only way to reach a `Rule`: a rule can't exist
without a constraint, and can't exist without a reason to give when it
fails.

And there is no `andShould()`. A rule states **one** requirement, the way a
good test makes one assertion — two requirements are two rules, each with
its own reason. This is also what keeps a reason honest: one sentence
explaining two unrelated constraints explains neither. Selectors still
accumulate, one at a time, through `that()` and `andThat()`.

```php
Rule::allClasses()
    ->that(new Selector\ResideInNamespace('Arkitect\Evaluate\Selector'))
    ->should(new Constraint\NotDependOnTheseNamespaces(['Arkitect\Evaluate\Violation*']))
    ->because('a selector decides what a rule is about and never reports anything');
```

That is a real rule from `run.php`, and its reason is what the output uses
as the rule's title — a rule that explains itself needs no separate label.

One decision worth knowing, since it isn't obvious from the types:
selectors combine with *and*, so a definite `No` from any of them settles
the class even when another came back `Unresolved`. The rule isn't about
that class either way, and reporting it would be noise.

`run.php` runs this codebase's own architecture as real rules rather than
as a demo: the stage ordering, that nothing under `Evaluate\Selector`
references `Violation`, and the two hexagon boundaries — no knowledge of
which library reads PHP source, none of who prints results. All pass,
which on its own proves nothing, so each was also verified to fail when
inverted: the selector-vs-`Violation` pattern aimed at `Evaluate\Constraint`
reports 39 violations, and the php-parser rule aimed at `Arkitect\Parser`
reports 45.

Writing them turned up a gap. Selectors combine with *and*, so `that()`
only narrows: "everything except `Arkitect\Parser`" cannot be said at all,
and `ResideInOneOfTheseNamespaces` — added for this — only lets the other
components be enumerated by hand. A negative selector is the real answer,
and it brings the twin-class question with it.

## PoC exit criteria

Set before writing implementation code, so the PoC has a defined end instead
of expanding into "the whole 2.0":

1. **Partially met, differently than planned.** The formal extraction of
   `CanParseClassTest.php`/E2E fixtures into a checklist was skipped (user's
   call — recover from `main` if a gap ever surfaces). Coverage happened a
   different way instead: `ParserTest.php` (written from `FileVisitor`
   knowledge) and `CollectTest.php` (the TDD trail) together cover
   nullable/union/intersection/DNF types, attributes, docblocks, property
   hooks, anonymous classes, `@throws` resolution — a broad set, just not
   verified against the literal old test names.
2. **Met.** The state-leak case (`Innocent` reporting zero dependencies) is
   fixed structurally, with a regression test (`ParserTest`/`CollectTest`).
3. **Met.** `ClassGraph::isA()` (`src/Resolve/ClassGraph.php`) answers purely
   from the parsed set — no reflection, no `is_a()`, no autoloading.
   Verified against this repo's own `vendor/`: a real two-hop `extends`
   chain resolves correctly, and querying an unrelated target through a
   chain that passes through an unparsed core class correctly comes back
   `Unknown` rather than a guessed `No`.
4. **Ongoing, not a one-time checkbox.** The resulting code reads as
   well-organized on review — no mutable shared state standing in for
   return values, clear separation between stages. Qualitative, not a
   metric, and the primary bar for this PoC given performance is explicitly
   deferred (see above) — a fast but tangled result does not pass; a clean
   but unoptimized one does.
