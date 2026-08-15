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

A `ParsedClass` declares only what the file says about itself: FQCN and its
own declaration line, unresolved `extends`/`implements`/`use trait` names,
type references, attributes, docblocks — all with line numbers. The class's
own declaration line matters as much as the others: it's what gives a
purely structural check (`IsFinal`, `HaveNameMatching`, no specific
referenced node) something to point at — see `Violation` below.

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

### 2. Resolve — in-memory, per run, not cached (and doesn't need to be)

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
originally proposed. Reflection was rejected: it reintroduces a runtime
dependency and isn't reliably cacheable, i.e. the same problem being removed
from the parse stage. `IsA`/`IsNotA`/`Implement`/`Extend`-family expressions
become graph queries here instead of runtime calls (`#169`).

Cheap enough not to need caching: linking, not parsing — no I/O, no AST.

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
- All multi-value constructors take `array`, never splat (`#599`) — no
  inconsistency left to standardize, and no variadic-must-be-last
  constraint blocking a second constructor argument later.

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
- **Applicability.** The real case is narrower than v1's opt-in via
  `method_exists`: `IsFinal` on an interface is not a violation the user
  can fix, because an interface *cannot* be final. Every genuine instance is
  determined by `ClassKind`, which `ParsedClass` already carries — so this
  becomes a narrow declaration ("this constraint judges concrete classes
  only") that `Rule` enforces and counts, not an arbitrary predicate that
  would quietly become a second selector. Not built yet; when it is,
  `RuleResult` grows a third number next to `checked`, which makes the skip
  visible in the report instead of silent as it is in v1.

### 4. Config and Report — the two public faces

**Config** is a fluent object, not a closure that mutates a parameter:

```php
return Config::create()
    ->root(__DIR__)
    ->add($rules);
```

No `ClassSet`. Its job — pairing "what to scan" with "which rules apply" —
is already handled by the universal root-scan (stage 1) plus per-rule
`that()` selectors (stage 3); keeping it around would be a second filtering
vocabulary for the same intent. Filtering is namespace-based
(`ResideInOneOfTheseNamespaces`, already exists), which covers the near-total
majority of cases, or, for the rare case where directory and namespace
disagree (generated code, monorepo packages, legacy trees), a new
filesystem-based expression:

```php
Rule::allClasses()
    ->that(new ResideInPath('packages/legacy'))
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
- Report is rebuilt from scratch against the new structured `Violation`.
- 2.0 is a hard break for third-party expression authors — no
  compatibility shim. v1 keeps being maintained in parallel.
- `Not`/`And`/`Or` composable expressions are explicitly out of this
  rewrite — not clearly a DX improvement over the existing twin classes.

## Open — not yet decided, do not treat as settled

- **Where `root()` comes from**: must be explicit in config, not inferred
  from cwd or from the config file's location — but the exact API isn't
  fixed yet.
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
- **Parse scope vs. check scope**: whether namespace filtering via `that()`
  is enough on its own to keep `vendor/` (parsed, for resolution) out of
  rule checks by default, or whether "what we parse" and "what we check by
  default" need to be separate concepts. See the note under stage 4.
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

## File access: a port, not a filesystem call baked into ProjectParser

`ProjectParser` doesn't touch the filesystem itself. It depends on
`FileRepository` (`src/FileSystem/`), a two-method interface (`files()`,
`read()`); `FilesystemFileRepository` is the only production
implementation. Not adopting a general hexagonal-architecture posture for
the whole codebase — `Parser` itself is already a pure domain service
without needing that vocabulary, and nothing else currently reads files —
this one seam is pulled out because the pain was already concrete: testing
`ProjectParser` meant real temp directories, `mkdir`/`chmod`/`rmdir` per
test. `InMemoryFileRepository` (test-only, under `tests/FileSystem/`)
replaces that with an in-memory fixture; `FilesystemFileRepositoryTest`
keeps a small, separate suite that exercises the real adapter against real
disk I/O, so the abstraction itself doesn't go unverified.

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

`Rule` holds selectors, one constraint and a reason, and returns a
`RuleResult` carrying three things
— `checked`, the violations, and the classes it couldn't decide about —
which is what it takes to answer both the zero-matched requirement and
"can this answer be trusted at all" (`isConclusive()`). Missing on purpose:

- **Applicability by kind** (see stage 3). It belongs to `Rule::check()`,
  which is what would do the skipping and the counting. The only design
  question left open in this stage.

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

`run.php` runs this codebase's own stage ordering as real rules rather
than as a demo: parsing depends on neither resolving nor evaluating,
resolving doesn't depend on evaluating, and nothing under
`Evaluate\Selector` references `Violation`. That last one is the design
decision from stage 3 kept honest by the tool itself. All of them pass —
which on its own proves nothing, so each was also verified to fail when
inverted: pointing the same selector-vs-`Violation` pattern at
`Evaluate\Constraint` reports 39 violations across 13 classes.

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
