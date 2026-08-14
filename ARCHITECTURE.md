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
is the PHP-core-class check (see stage 1) — reflection is acceptable there
specifically, and even that can be implemented later, since it's narrow and
contained.

## The four stages

### 1. Parse — pure, per file, cacheable

```
(file content, target-php-version, annotation flag) → ParsedFile
```

A `ParsedFile` declares only what the file says about itself: FQCN and its
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
philosophy rules out (*Reliable first, fast second*). The PHP-core-class
filter moves out of parsing entirely, into resolve or evaluate (stages 2/3,
neither of which is cached the same way), where a reflection-based check is
a deliberate, scoped exception to "no runtime calls" — narrow enough that
its actual implementation can wait.

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
all ParsedFile (project + vendor) → symbol graph → resolved ClassDescription
```

Turns names into links: for each class, the transitive ancestor chain
through `extends`/`implements`/traits, including ancestors that live in
`vendor`. If an ancestor can't be found anywhere in the parsed set, the
graph says so explicitly — it does not silently resolve to `false`, which is
what `IsA`'s current `is_a($fqcn, $allowed, true)` does when the class isn't
autoloadable.

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

### 3. Evaluate — the `Expression` contract, rewritten

```php
interface Expression
{
    public function appliesTo(ClassDescription $class): bool;   // was opt-in via method_exists — #669
    public function evaluate(ClassDescription $class): Violations; // was void, mutated a param — #670
}
```

Consequences that fall out of this, not separate work:
- `Violation` identity becomes structured data (class, expression, detail,
  line) instead of a rendered sentence parsed back with `', but '`/`'
  because '` string matching — the baseline keys on data, not prose
  (`#671`). Unlike today (`Violation::$line` is nullable, populated only by
  dependency-type checks via `ClassDependency`), `line` is never null: an
  expression that references a specific node (a dependency, an `extends`)
  uses that node's line; anything purely structural falls back to the
  class's own declaration line, which `ParsedFile` now carries (see stage
  1). This is what actually backs the Report requirement below that every
  violation carries `file:line` — without it, that requirement had nothing
  guaranteeing it for roughly half of today's expressions.
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

`appliesTo()`'s meaning is not yet settled and needs real design work, not
just a signature: it means "class excluded from the selector" in `that()`
but "constraint vacuously holds" in `should()` — same method, two different
semantics, and it has to interact cleanly with the report requirement below
that "matched nothing" must be visibly different from "matched everything
and found no violations." See Open.

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
compatibility shim for third-party `Expression` implementations and no
soft-deprecation window within 2.0 itself — the contract changes
(`evaluate()`'s signature, `appliesTo()` becoming required) are exactly the
kind of thing a shim can't paper over cleanly. v1 is expected to keep being
maintained in parallel for users who aren't ready to move, rather than 2.0
trying to ease everyone across at once.

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
- Parse is pure and cacheable. The PHP-core-class check is the one
  deliberate exception to "no runtime calls" — it may use reflection,
  confined to resolve/evaluate (never parse), and its implementation can
  wait, since it's a narrow, contained problem.
- `vendor/` is parsed through the same pipeline as project code — primarily
  because inheritance resolution needs it, not primarily for caching.
- Inheritance resolution is a graph built at resolve time, over parsed
  data only — never reflection.
- `Expression::evaluate()` returns `Violations`; `appliesTo()` is a required
  contract method (its exact semantics are still open, see below).
- Every `Violation` carries a line — never null. `ClassDescription` stores
  its own declaration line specifically so structural checks (no specific
  referenced node) have a fallback instead of reporting no line at all.
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
- 2.0 is a hard break for third-party `Expression` authors — no
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
- **Exact shape of "unknown ancestor"** in the resolve graph: decided to be
  explicit rather than silently `false`, but the API surface (exception?
  tri-state return? sentinel value on `ClassDescription`?) isn't designed.
- **`appliesTo()`'s dual meaning** — "excluded by the selector" in `that()`
  vs. "constraint vacuously holds" in `should()` — is a real, unresolved
  conceptual problem, not a deferred nice-to-have. Needs an actual answer
  before the contract in stage 3 is final, and has to reconcile with the
  report requirement that zero-matched and zero-violations be visibly
  different outcomes.
- **Parse scope vs. check scope**: whether namespace filtering via `that()`
  is enough on its own to keep `vendor/` (parsed, for resolution) out of
  rule checks by default, or whether "what we parse" and "what we check by
  default" need to be separate concepts. See the note under stage 4.
- **Resolve-graph edge cases** — duplicate FQCNs, inheritance cycles, trait
  conflicts, diamond interfaces: not designed for; handle each when a real
  case surfaces it rather than speculatively now.

## Parser component: design note

First attempt at the collecting engine ported `FileVisitor`'s shape
(dispatch-per-node-type methods over a `NodeVisitorAbstract`, a stack of
mutable accumulators to fix the leak bug). It worked, but was flagged as
not clearly better than what it replaced — still one stateful object being
mutated by a long sequence of callbacks, just with a stack instead of a
single shared builder.

Rebuilt with actual TDD (one failing test at a time, no upfront design),
landed on something structurally different: `ClassCollector` holds **no
instance state at all** — every method is a pure function of its
arguments. Declarations and the facts inside them are found by two
independent recursive walks: `findClasses` looks for named class-like
declarations; `collectDependencies`/`collectTraits`/`collectDocBlocks` look
for facts inside *one* declaration's own body, and are only ever called
with that body as their root. The state-leak bug (a top-level function's
parameter attaching to the next class) isn't prevented by a check — the
fix in the first attempt was `if (stack empty) return;` repeated at every
call site — it's structurally impossible: there is no code path that hands
a top-level function's body to the dependency walk. Confirmed by writing
the regression test *after* the design existed, expecting to have to add a
guard, and it passed unmodified.

125 fewer lines than porting the old shape, one file instead of two,
zero mutable fields instead of a stack. Kept as the resolved shape for
this component; see `tests/Parser/CollectTest.php` for the scenario-by-
scenario TDD trail and `tests/Parser/ParserTest.php` for the broader
scenario suite it was checked against.

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

## PoC exit criteria

Set before writing implementation code, so the PoC has a defined end instead
of expanding into "the whole 2.0":

1. The syntax-edge-case scenarios in `CanParseClassTest.php` and the E2E
   fixtures are re-implemented as fresh tests against the new parse stage,
   and pass — ported as scenarios, not as code.
2. The state-leak case above (`Innocent` must report zero dependencies) is
   fixed.
3. `IsA` (and the rest of the inheritance family) answers purely from the
   resolved graph — no runtime call, provably (e.g. works correctly even
   when the target class isn't autoloadable in the process running
   `phparkitect`).
4. The resulting code reads as well-organized on review — no mutable
   shared state standing in for return values, clear separation between the
   four stages. This is a qualitative call, not a metric, and it is the
   primary bar for this PoC given performance is explicitly deferred (see
   above) — a fast but tangled result does not pass; a clean but unoptimized
   one does.
