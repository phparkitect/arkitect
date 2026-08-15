# PHPArkitect 2.0 — architecture PoC

A working record for the `v2-poc1` branch, written so the branch could be
rebuilt from nothing: the decisions, the reasons that are not recoverable
from the code, and the traps that cost time to find. Not a spec, and not a
history — when this branch merges its content dissolves into `docs/` and
into issues, and this file does not survive the merge.

Everything here is present tense and describes what is true now. Where a
decision reversed an earlier one, only the outcome is recorded; the path is
in the commit messages, which is where it belongs.

Guiding reference: `docs/philosophy.md`, already merged on `main`.

## Scope: a full rewrite

Every line in `src/` is written fresh. Nothing from `main` is kept,
patched, or run side-by-side as a fallback. There is no intermediate state
where old and new implementations coexist.

The one thing that crosses over is not code: it is *knowledge* of which
PHP-syntax edge cases must not regress, six years of "someone's real code
broke the parser", encoded in `main`'s
`tests/Unit/Analyzer/FileParser/CanParseClassTest.php` and E2E fixtures.
The plan was to extract that into a checklist first; that step was skipped
by decision, and coverage was rebuilt from scratch instead (see *PoC exit
criteria*). Those files remain the place to look if a gap ever surfaces.

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
  `Innocent` is reported as depending on `Alpha`. It should depend on
  nothing. A correctness bug today, independent of 2.0 — fixed here by
  making parsing stateless per class, not by routing around it.

- **`vendor` parsing is cheap**, benchmarked against this repo's own
  `vendor/` (2,515 files, 20MB) with v1's parser:
  - cold parse: 5.90s (427 files/s), peak memory 52MB
  - cache serialized: 24.5MB (0.9MB gzipped)
  - warm (unserialize): 0.19s — **~3% of cold**
  - cache validation (read+hash every file): 0.05s — negligible, so no
    mtime-based shortcuts are needed
  - not measured, still a risk: memory holding tens of thousands of parsed
    classes at once on a large project (vendor trees of 15–30k files are
    common). Time is not expected to be the problem; memory might be.

## Priority: reliable and comfortable before fast

Performance work is out of scope for this PoC. The numbers above are
evidence that the approach is not obviously too slow, not a target — no
throughput or memory figure gates this. Per *Reliable first, fast second*:
a correct and pleasant tool first, then a fast one. A fast but tangled
result does not pass; a clean but unoptimized one does.

## The pipeline

Four sections follow, but only three are components. "Resolve" was a stage
on the map drawn before the code existed; what exists is a graph and the
act of building one.

### 1. Parse — pure, per file, cacheable

```
(file content, target-php-version, annotation flag) → ParsedClass
```

`Parser\ClassParser` turns one file's source into classes;
`Parser\RepositoryParser` walks a `FileRepository` and assembles a
`ParseResult`.

A `ParsedClass` declares what the file says about itself: FQCN, its own
declaration line, unresolved `extends`/`implements`/`use trait` names, type
references, attributes, docblocks — all with line numbers. Its own
declaration line matters as much as the rest: it is what a purely
structural check (`IsFinal`, with no specific referenced node) points at.

It records the **type, not the spelling**, where the two disagree: an enum
carries `isFinal: true` even though the keyword is a syntax error on an
enum. This is language knowledge and needs no runtime call, and the
alternative is worse — recording `false` makes every "must be final" rule
report an enum nobody can fix. The same principle governs names, below.

**Hard constraint: zero runtime calls in this stage.** No `class_exists`,
no `ReflectionClass`, no `is_a()`. This is what v1's
`ClassDescriptionBuilder::isPhpCoreClass()` violates — it makes parse
output depend on which extensions happen to be loaded on the machine
running it, the "cache that is occasionally wrong" the philosophy rules
out. That filter lives in `Resolve\InternalClasses` instead, outside the
cached stage.

`vendor/` runs through the identical pipeline, no special-casing. This is
required for inheritance resolution to be correct at all: a project class
extending a vendor class needs that class's own ancestors. Caching is a
secondary benefit — vendor does not change between runs, so its hit rate
is effectively 100% after the first.

**Cache key**: file path + content hash + target-php-version + annotation
flag + arkitect version. Not mtime: validation cost is negligible even at
2,500 files.

#### How the collector avoids the state leak

`ClassCollector` holds no instance state — every method is a pure function
of its arguments. Declarations and the facts inside them come from
independent recursive walks: `findClasses` looks for named class-like
declarations, while `collectDependencies`/`collectTraits`/`collectDocBlocks`
look for facts inside *one* declaration's body and are only ever called
with that body as their root. The state leak is structurally impossible as
a result, not guarded against: no code path hands a top-level function's
body to the dependency walk.

A stateful alternative — a `NodeVisitorAbstract` with a stack of mutable
accumulators pushed and popped around declarations — also fixes the leak,
but by checking "is the stack empty" at every call site: a guard to
remember rather than a state that cannot exist. Rejected for that reason;
do not reintroduce it.

#### `@throws` resolution

`@throws` tags become dependencies two ways: a leading-`\` name is already
fully qualified, and a single-segment short name resolves through the
file's own `use` imports (collected by a separate walk of the same shape).
A short name with no matching import is left unresolved, deliberately not
guessed as "probably the same namespace": without redoing full namespace
resolution there is no reliable way to tell a same-namespace class from a
typo, and being silently wrong is worse than not extracting it. Property
hooks (8.4) and `use function`/`use const` needed no special handling — the
per-node dispatch and generic recursion already do the right thing,
confirmed by tests rather than assumed.

### 2. The class graph

```
all ParsedClass (project + vendor) → ClassGraph → Membership
```

In memory, per run, not cached: linking, not parsing — no file I/O, no AST.

`ClassGraph` is the interface: `isA()` and `hasAncestor()`, each answering
`Membership::Yes|No|Unknown`. `ParsedClassGraph` is the one implementation,
an index over the parsed set. If an ancestor cannot be found anywhere, the
graph says so rather than silently resolving to `false` — which is what
v1's `is_a($fqcn, $allowed, true)` does when the class is not autoloadable.

This is what replaces the reflection-based approach `#582` proposed:
`IsA`/`Implement`/`Extend` are graph queries over parsed data, not runtime
calls (`#169`). Reflection was rejected as *the answer* because it
reintroduces a runtime dependency and is not reliably cacheable. One narrow
question survives, because parsed data cannot answer it even in principle:

**Internal classes.** A class compiled into PHP — `RuntimeException`,
`ArrayObject`, equally `PDO` — has no source file, so it can never be in
the parsed set however much is parsed. Treating that dead end as unknown
made every descendant of one unanswerable: `App\MyEx extends
\RuntimeException` came back `Unknown` for *every* target, which is every
custom exception in every project. So when a name is missing from the
parsed set, `ClassGraph` asks `Resolve\InternalClasses`:

- internal ancestor, user-defined target → **`No`**, definitively. An
  internal class only inherits from other internal ones, since no C
  extension can name a user-defined type. An inference, not a lookup.
- internal ancestor, internal target → **ask the runtime**. Deterministic,
  and the same answer on any machine with the same extensions loaded.
- anything else → **`Unknown`**, which now means only what it should: this
  name has source somewhere and it was not in what we parsed.

`InternalClasses` is named for what it tests, `ReflectionClass::isInternal()`,
true for extension classes as well as PHP's own. It keeps autoloading off:
internal symbols are present without it, and running the autoloader would
execute project code to answer a name lookup.

**`hasAncestor()` is not `isA()`**, and `Extend` would be a synonym for
`IsA` without the distinction: it follows the `extends` chain only, so an
implemented interface is a supertype but not an ancestor, and a class is a
subtype of itself but not its own ancestor. Both queries match a declared
parent by name *before* walking into it, so extending a class nobody parsed
still answers definitively.

Verified across the boundary that justifies parsing `vendor/` at all:
`tests/Resolve/ProjectAndVendorTest.php` parses a project-side fixture and
nikic/php-parser's real source, merges them into one graph, and confirms a
direct and a transitive relationship that only resolve because the vendor
side's own ancestor chain is visible (`App\MyVisitor extends
NodeVisitorAbstract`, which itself `implements NodeVisitor` — a real edge
inside php-parser, not a fixture).

Not designed for, deliberately: duplicate FQCNs (polyfills defining a class
twice), inheritance cycles, trait conflicts, diamond interfaces. Handle
each when a concrete case surfaces it.

### 3. Evaluate — two contracts, not one

```php
namespace Arkitect\Evaluate\Selector;

interface Selector      // what the rule is about — that()
{
    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection;
}

namespace Arkitect\Evaluate\Constraint;

interface Constraint    // what it requires — should()
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome;
}
```

`evaluate()` returns rather than mutating an accumulator passed in (`#670`),
which is what lets `Violations` be immutable. `ClassGraph` is a parameter
and not a constructor dependency, because the config builds constraints
before anything has been parsed.

**Selectors and constraints are separate classes**, not one class
implementing both interfaces, even where they share a name
(`ResideInNamespace` exists in both namespaces). Two reasons, both load
bearing:

- A constraint is strictly richer than a predicate.
  `DependOnlyOnTheseNamespaces` reports one violation per offending
  dependency, each on its own line, and no boolean expresses that. The type
  system now rejects `that(new DependOnlyOnTheseNamespaces(...))`, which v1
  accepted and did something incoherent with.
- The same question gets different answers in the two positions. An
  unresolvable ancestor chain is recorded per class by a constraint; for a
  selector it decides nothing, so `matches()` returns a `Selection` rather
  than a bool. One shared class would have forced one answer.

The duplicated names cost nothing: both `ResideInNamespace` classes
delegate to the same `Pattern`, so the logic lives once. Nothing under
`Evaluate\Selector` references `Violation`, which `run.php` checks as a
rule rather than leaving as an intention.

#### Three outcomes, not two

A violation claims the code breaks a rule. Two other things are not that,
and each has its own channel:

- **Unresolved** — the tool could not decide, because an ancestor was never
  parsed. A gap in our input, not a fault in the code. Folding it into
  violations had a concrete cost: the baseline keys on violations, so a
  resolution failure could be accepted once and hidden forever, freezing a
  broken parse scope into the project's accepted state. `isConclusive()` is
  false whenever anything went unresolved, whatever the violation count.
  Collected rather than thrown, for the reason `ParsingError` is: one
  undecidable class must not cost the report on the other five hundred.
- **Not applicable** — the requirement is impossible for this class.
  `IsFinal` on an interface asks for an edit the compiler refuses, as it
  does on traits and enums, and on abstract classes.

  Applicability is **not** determined by `ClassKind`: an abstract class is
  an ordinary class and still cannot be final, so a design keyed on kinds
  would have looked complete and quietly missed it. The boundary is *who
  decides* — the language or the user. "An interface cannot be final" is a
  fact about PHP; "classes in `App\Legacy` are exempt" is intent and
  belongs in a selector.

  It is carried, not printed. Someone who writes "domain objects must be
  final" means the classes and is not surprised the interface beside them
  was skipped; a count on every run is noise about something they cannot
  act on, and noise teaches people to ignore output. `RuleResult` keeps the
  data for a report or a `--verbose`, and surfaces it in the one case where
  silence misleads: `judgedNothing()`, a rule that matched classes and could
  judge none.

#### `appliesTo()` does not exist

It was never one problem. It meant "excluded from the selector" in `that()`
and "constraint vacuously holds" in `should()` because a single type
occupied both positions and the method had to work out at runtime which one
it was in. Two positions with two types removes the ambiguity instead of
settling it, so the method is not built. Its two halves are answered
separately: **scope** by `Selector` plus `RuleResult::$checked`, and
**applicability** by the third `Outcome` channel above.

`matchedNothing()` and `judgedNothing()` are deliberately separate signals:
a rule that selected nothing is fixed in the `that()`, a rule that could
judge nothing in the `should()`.

#### The rule DSL

```php
Rule::allClasses()
    ->that(new Selector\ResideInNamespace('Arkitect\Evaluate\Selector'))
    ->should(new Constraint\NotDependOnTheseNamespaces(['Arkitect\Evaluate\Violation*']))
    ->because('a selector decides what a rule is about and never reports anything');
```

v1's shape, with two changes. `Rule::allClasses()` returns a `RuleDraft`
rather than a half-built `Rule`: the incomplete states have their own types,
and `because()` is the only way to reach a `Rule`, so a rule cannot exist
without a constraint or without a reason to give when it fails.

And there is no `andShould()`. A rule states **one** requirement, the way a
good test makes one assertion — two requirements are two rules, each with
its own reason, which is also what keeps a reason honest. Selectors
accumulate one at a time through `that()` and `andThat()`.

Selectors combine with *and*, so a definite `No` from any of them settles
the class even when another came back `Unresolved`: the rule is not about
it either way, and reporting that would be noise.

`Rule` holds selectors, one constraint and a reason. `RuleResult` carries
`selected` and `checked` separately — they differ when a constraint could
not mean anything for some of the classes picked — alongside the
violations, the classes it could not decide about, and the ones it could
not judge.

Direct versus transitive is a `Depth` parameter on `Implement`/`Extend`,
not a second pair of classes: twin classes are the wart `#516` flags, and
they multiply as soon as negations arrive. `Depth::Transitive` is the
default because the alternative is a correctness trap — a class inheriting
an interface from its parent really does implement it. `Depth::Direct`
reads the declaration and never consults the graph, so it cannot return
`Unknown`.

`Violation` is structured data (fqcn, filePath, line, constraint, detail),
not a rendered sentence parsed back with `', but '` string matching — the
baseline keys on data, not prose (`#671`). `line` is never null, with two
named constructors for the two cases: `createAt()` takes the
`TypeReference` and points at that node's line, `create()` falls back to the
class's own declaration line.

Explicitly **not** part of this rewrite: `Not`/`And`/`Or` composable
decorators replacing the 17 `Not*`/`IsNot*` twin classes
(`#516`/`#394`/`#395`/`#387`). A returning `evaluate()` makes them
technically possible, which is not on its own a reason; they are not
clearly a DX win over twin classes that cause no confusion in practice.

Note that `DependOnlyOnTheseNamespaces` and `NotDependOnTheseNamespaces`
are *not* negations of each other — one lists what is permitted and forbids
the rest, the other lists what is forbidden and says nothing about the rest.

### 4. Config, Check and Report

```php
return Config::create(__DIR__)
    ->add($rules)
    ->targetPhpVersion('8.1');
```

The root is a constructor argument and everything optional is fluent, which
makes a config file show at a glance what has to be given. It is never
inferred from the working directory or from where the config file sits: a
run has to mean the same thing wherever it was started from.

`Config` is mutable, its setters assigning one field and returning `$this`.
Rebuilding an immutable config through a constructor taking every field
meant `add()` naming the root and the PHP version while doing neither, and
PHP 8.5 has no `clone with`. A config is written once in a file and then
read, so immutability buys little here. The cost, since it is real: the
optional properties are publicly assignable, PHP having no package-private
and a private one forcing a getter whose name would collide with its setter.

`TargetPhpVersion` is a backed enum — it always was one in disguise, a
const array of valid values and a constructor checking membership. Unset,
it defaults to the interpreter running arkitect; a PHP newer than any case
fails with a message saying to pin the version rather than guessing. The
honest trade-off of the default: the same `phparkitect.php` parses
differently depending on which PHP runs it, which is exactly the case
`#650` says to pin explicitly. `#650` also moves `target-php-version` and
the baseline path out of CLI flags into the config, leaving the CLI only
bootstrap (`--config`, `--autoload`) and one-off overrides (`--format`,
`--stop-on-failure`, `--skip-baseline`).

**Parse scope and check scope are not the same scope.** `vendor/` *must* be
parsed or inheritance cannot be resolved; it *must not* be checked, or a
config that forgets a namespace selector reports thousands of violations in
code its author cannot change. Namespace filtering via `that()` was never
enough on its own: selectors narrow what a rule is about, they are not a
substitute for knowing whose code it is.

`Codebase` is where the line is drawn: one parse, two views of it —
`ownClasses`, what rules may judge, and `graph`, what names resolve
against. Not in `Config`, because nobody declared it; it is our policy, and
it moves to `Config` the day a project can override it. Against this repo,
the two views differ by roughly 2,600 classes.

Nothing downstream had to change for this: `Rule::check(array $classes,
ClassGraph $graph)` already took the classes to judge and the graph to
resolve against as separate arguments, for the unrelated reason that a
constraint cannot hold the graph as a constructor dependency.

`Command\Check` orchestrates — parse, split into a `Codebase`, one
`check()` per rule, collect — taking the parser injected and `Config` as its
argument, and returning a `CheckResult`. It knows neither where files come
from nor where results go. `isClean()` is on the result, because whether a
run passed is not presentation.

No `ClassSet`: its job is split between the root scan, the `vendor/` line,
and per-rule selectors. Filtering is namespace-based
(`Selector\ResideInOneOfTheseNamespaces`). For the rare case where directory
and namespace disagree — generated code, monorepo packages, legacy trees —
the intended answer is a path-based selector, `ResideInPath`, **which is not
built**: no case has needed it yet.

The closure-config form is removed rather than kept as an alternative:
accepting both means every tutorial shows a different shape (*one way to do
each thing*), and it removes the footgun where a `phparkitect.php` builds
`$rules` and forgets `$config->add()` — a silent green today. The
`Architecture`/`RuleBuilders` DSL is dropped too, which was already the
deprecation direction pre-2.0.

**Migration policy**: 2.0 is a hard break. No compatibility shim for
third-party expression authors and no soft-deprecation window within 2.0 —
the contract changes (`Expression` split into `Selector` and `Constraint`,
`evaluate()`'s signature) are what a shim cannot paper over. v1 keeps being
maintained in parallel.

#### Report

Rewritten from scratch — unlike the analyzer it holds no accumulated
bug-fix knowledge worth preserving. `Report` is the interface, `TextReport`
the adapter for a human at a terminal.

Designed around *when* the output is read: in CI only when it fails and
often only the last lines; locally over and over while fixing; and on first
adoption as a wall of hundreds of violations. So the failing run is what
the format is for, and a clean run is one line.

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
  IDEs turn into a link — the detail that most changes how this feels
  locally. Paths are relative to the root, which is why the root being the
  project root matters: scanning `src/` directly yields
  `Evaluate/Selector/Extend.php`, which does not resolve from the project
  root and is not clickable.
- **Violations group under the rule's `because()`**, read once instead of
  repeated on every line as in v1. No new DSL clause was needed; the text
  was already there.
- **The class name is not repeated.** The first draft had it; running the
  report against real code rather than a mock killed it immediately, since
  with real paths the FQCN was two thirds of the line and said only what
  `file:line` already said.
- **Rules that pass are not printed.** Ten green lines hide the two red
  ones; the summary says how many ran. A `--verbose` can list them.
- **Deterministic order** (file, then line), so two runs over the same code
  produce identical bytes. The baseline will depend on it.
- **Everything is printed, never truncated** — hiding violations to keep the
  output short is the baseline's job, not the formatter's.
- **A rule that matched zero classes, or a config with zero rules, must not
  report success.** Checking nothing and finding nothing have to look
  different.

Exit codes: `0` clean, `1` for violations *and* for anything left unchecked
(unresolved classes, unparsable files), `2` for a usage error such as a
root that is not a directory. One code for both failure kinds on purpose: a
run that could not check part of the project has not passed, and splitting
them would be a distinction CI cannot act on differently.

#### Baseline

The violations a project has decided to live with, so arkitect can be
adopted without fixing everything first. What decides whether it is any
good is what identifies a violation across runs, and v1 answers that twice
badly: it keys on the line number — so much so that it ships an opt-in
setting to ignore line numbers, a trap you have to learn about before you
can turn it off — and on the rendered error string.

Identity here is the class, the constraint, and the constraint's own `key`:
the name the violation is about beyond the class — the forbidden
dependency, the interface not implemented, the namespace expected. It comes
from the rule's parameters and never from the message, so rewording a
message cannot invalidate a baseline in the wild. `Violation::createAt()`
fills it from the `TypeReference` it already had.

**Everything stored is compared**, which is what keeps the file honest: no
line, no path, no message, nothing that can go stale without meaning
anything. Entries are sorted, since the file is committed and read in
diffs.

Two commands, both running the check with any existing baseline ignored —
reading the current one would hide from them exactly the violations they
exist to record. They differ in what they do next, which is why both exist:
`generate-baseline` accepts what is there now and replaces the file;
`prune-baseline` only ever shrinks, keeping the entries that still match
something, so a violation introduced since is left to fail rather than
quietly accepted. Without pruning, a baseline keeps permission for work
already done and nobody notices.

A configured path that holds no baseline stops the run rather than
proceeding with an empty one, which would report every known violation as
though it were new.

#### The CLI

`bin/arkitect`, with `Cli\Console` as the driving adapter: it translates
argv and a config file into a run, and a `CheckResult` back into text. It
holds no decision of its own, and it is the composition root — everything
concrete is built there. A rule in `run.php` keeps that honest: nothing
outside `Arkitect\Cli` may depend on it.

The CLI is not a port. The port on the driving side is the commands' own
API, and an interface between them and their only caller would be ceremony.

**No console library.** `getopt()` looks like the middle ground and is
unusable: it stops at the first non-option argument, so
`arkitect check --config=x` returns no options at all, silently. That
leaves a dependency or thirty lines, and thirty lines is what three
commands and two flags need — arkitect keeps a single production
dependency. Two behaviours come from writing it: one accepted spelling
rather than four synonyms, and an unknown flag is an error rather than an
option quietly ignored, since a mistyped `--skpi-baseline` should not run
something other than what was asked.

Surface: `check` (the default), `generate-baseline`, `prune-baseline`;
`--config=`, `--skip-baseline`, `--help`. `--format` waits for a second
`Report`, `--stop-on-failure` is pointless with a report that prints
everything and a baseline that hides what you accepted, and `--autoload`
until something needs it.

Loading `phparkitect.php` is `require` of a user file that returns a
`Config` — arbitrary code execution, and inherently "how the user talks to
us", so it lives on the driving side rather than behind a port. `Console`
is tested against a real directory and a real config file, the way the
filesystem adapter is.

## Ports and adapters, arrived at rather than adopted

`FileRepository` was pulled out first on its own merits: testing the parser
meant real temp directories and `mkdir`/`chmod`/`rmdir` per test. This was
explicitly *not* meant to be a general hexagonal posture. It became one
anyway, one seam at a time, each for a reason of its own:

- **`FileRepository`** — so parsing is testable without disk.
  `FilesystemFileRepository` is the production adapter, with a small suite
  against real I/O so the abstraction itself is not unverified;
  `InMemoryFileRepository` (test-only) is what lets `CheckTest` run every
  stage together in memory.
- **`ProjectParser`** — the port `Check` depends on, with `RepositoryParser`
  reading a `FileRepository` and `ClassParser` parsing one file. The cache
  planned in stage 1 is a decorator on this interface; that is what the port
  is for, more than swappability nobody wants.
- **`ClassGraph`** — the questions rules ask, separated from
  `ParsedClassGraph`. Not for a second implementation: the class reached for
  reflection through `InternalClasses`, so a runtime call was sitting in
  what was being called domain.
- **`Report`** — how results leave. A machine-readable format is a known
  requirement, which is what makes the interface more than speculation.

What this is *not*: a `Domain/` directory. The tree groups by component
(`Parser`, `Resolve`, `Evaluate`, `Report`), and layering it as well would
put two organizing principles in one tree while nesting every name a level
deeper. The boundary such a directory would document is already *checked*
by rules in `run.php`, which a directory cannot do.

`Command/` holds `Check`, `GenerateBaseline` and `PruneBaseline`, singular
like `Constraint/` and `Selector/` beside it.

## Names: one spelling, enforced by `Fqcn`

Every class name is fully qualified with no leading separator. Not a style
preference: `ClassGraph` indexes classes by this exact string, so accepting
both `App\Foo` and `\App\Foo` would silently make them two unrelated types,
and a rule about one would match nothing.

The form is not really a choice either — it is what php-parser's
`toString()` returns, what a class's own `namespacedName` gives, and what
`Foo::class` evaluates to, which is how people write names in rules. The
leading `\` belongs to source syntax, not to the name as a value.

`Fqcn` holds the rule in one place and **normalizes rather than rejects** a
leading separator, since the two name the same class beyond ambiguity and
the first is what people write when copying from code. Exactly one
separator is stripped, so `\\App\Foo` still fails: that is not a name
anyone meant. `TypeReference` also requires `line >= 1`, because php-parser
answers `-1` for a node with no position and that value would otherwise
reach a violation reported at `src/Foo.php:-1`.

`Fqcn` is used by `TypeReference`, by `ParsedClass` (whose `shortName()`
and `namespaceName()` derive from it), and by every constraint and selector
taking a target name. `Pattern` normalizes the same way without using it,
since a pattern carries wildcards and is not a class name.

## Traps found by building it

The parts that cost real time to discover, and would be rediscovered the
same way by anyone starting over.

- **Internal classes make everything unanswerable.** Before the inference
  in stage 2, every class descending from `\RuntimeException` — every
  custom exception in every project — answered `Unknown` for every query.
  A rule that treats `Unknown` as a violation then fires on ordinary code.
- **A pattern must mean one thing.** v1 gave wildcard patterns to `fnmatch`
  against the whole name and dropped the "anything beneath it" half that
  wildcard-free patterns get, so `App\*\Domain` silently matched nothing.
  Here a pattern means the same thing either way, and is validated when
  constructed rather than halfway through a run.
- **A leading separator fails in the worst direction.**
  `IsA('\App\Contract')` matched nothing stored, so every class in a
  codebase that satisfied the rule was reported as violating it;
  `ResideInNamespace('\App\Domain')` merely selected nothing.
- **php-parser answers `-1`** when a node has no position, and nothing
  stopped that reaching a report as `src/Foo.php:-1`.
- **Enums are final** but cannot say so, and recording the keyword's absence
  made every "must be final" rule report an enum nobody can fix.
- **v1 allowed more than it looked.** In `DependOnlyOnTheseNamespaces` it
  skipped a dependency whenever the class sat anywhere beneath the
  dependency's namespace, silently permitting every parent namespace. Here
  only the class's own namespace is implicit.
- **`that()` only narrows**, so for a while "everything except
  `Arkitect\Parser`" could not be said and the rule about php-parser listed
  the other namespaces by hand — a list that goes quietly out of date the
  moment a component is added. `Selector\NotResideInNamespace` says it
  properly, and covers 98 classes where the list covered four namespaces
  somebody remembered.

Two habits came out of these, both cheap and both in `CLAUDE.md`: check a
rule against real input before enforcing it — the name and line rules were
validated against 26,680 type references and 2,620 FQCNs from this repo's
own `vendor/` — and invert an architecture rule to watch it fail before
trusting that it passes.

## Non-negotiables

The handful that must survive any reshaping. Everything else above is
explained where it belongs.

- Parsing makes zero runtime calls, so its output is identical everywhere
  and can be cached. The internal-class check is the one scoped exception
  and lives outside parsing.
- Inheritance resolves over parsed data, never over reflection — except the
  narrow "is this internal", which parsed data cannot answer at all.
- "Could not determine" is never a violation, and never silent.
- Every violation carries `file:line`.
- Checking nothing and finding nothing must look different.
- `vendor/` is parsed and not checked.
- Multi-value constructors take `array` and not a splat **in the classes a
  user writes in a config** (`#599`) — that is where v1's inconsistency
  lives, and where a second argument would otherwise be blocked, as
  `Implement(string $target, Depth $depth)` shows. Internal collections use
  typed variadics instead, so PHP checks the element type rather than a
  `list<X>` docblock and a hand-written loop.
- 2.0 requires PHP `^8.5` to run, independent of `TargetPhpVersion`
  (8.0–8.5), which is what the *analysed* project targets — the same
  distinction PHPStan, Psalm and Rector make.

## Open — not decided, do not treat as settled

- **Multi-root / monorepo**: parked. Addressed via `ResideInPath` when a
  concrete case shows it is needed, not by reintroducing `ClassSet`-like
  roots. Revisit if `ResideInPath` turns out not to be enough.
- **Default exclusions for the root scan** (`.git`, build and cache
  directories, deliberately broken parse-error fixtures): deferred by
  product decision, not a blocker. Revisit when it is a real problem.
- **Cache storage and invalidation**: project-local directory versus system
  cache dir; whether `vendor`'s cache invalidates as a block on
  `composer.lock` rather than per file.
- **Graph edge cases**: duplicate FQCNs, inheritance cycles, trait
  conflicts, diamond interfaces.
- **Negation on the constraint side.** Nothing has asked for it, so
  nothing is built. When something does, it is a class named for what it
  does, like `Selector\NotResideInNamespace` — not a `Not` decorator.

  The decorator is tempting for selectors, where negation is genuinely
  clean: a selector answers `Yes|No|Unresolved` and produces neither prose
  nor identity, so negating it is swapping two of three. It does not
  survive the trip to constraints. A satisfied constraint produces nothing,
  so a decorator has no message and no `key` to build the violation it now
  has to report; and a constraint like `DependOnlyOnTheseNamespaces`
  reports one violation per offending dependency, each on its own line,
  which negates to a single violation about the class — a different shape,
  losing what made it useful. Making that work needs a narrower contract
  with two phrasings per constraint, composed keys, and a rule that
  negating `Unknown` stays `Unknown`: a small language for saying "not".

  Which leaves the reason it is a class on both sides rather than a
  decorator on one: users would otherwise learn two ways to negate,
  depending on which half of a rule they are writing.

## Working on this branch

`make test`, `make lint`, `make fix`, `make run`. There is no
`phpunit.xml`, so the suite path has to be passed — which is what the
Makefile is for. `CLAUDE.md` carries the conventions that outlive this
document.

`run.php` is not the CLI — `bin/arkitect` is. It stays because it runs
arkitect against its own architecture: the stage ordering, that nothing under
`Evaluate\Selector` references `Violation`, and the two hexagon boundaries
— no knowledge of which library reads PHP source, none of who prints
results. Each was verified to fail when inverted, reporting violations by
the dozen, because a rule that passes proves nothing until it has been
seen to bite.

## PoC exit criteria

Set before writing implementation code, so the PoC has a defined end rather
than expanding into "the whole 2.0".

1. **Partially met, differently than planned.** The formal extraction of
   `CanParseClassTest.php` and the E2E fixtures into a checklist was
   skipped by decision. Coverage was rebuilt instead: `ParserTest` and
   `CollectTest` together cover nullable/union/intersection/DNF types,
   attributes, docblocks, property hooks, anonymous classes and `@throws`
   resolution — a broad set, just not verified against the old test names.
   Recover from `main` if a gap surfaces.
2. **Met.** The state leak is fixed structurally, with a regression test.
3. **Met.** `ClassGraph` answers from the parsed set, with the one scoped
   exception above: no `is_a()`, no autoloading, and reflection only for
   "is this name internal". Verified against this repo's own `vendor/` — a
   real two-hop `extends` chain resolves, and a target reachable only
   through an unparsed user-defined class comes back `Unknown` rather than
   a guessed `No`.
4. **Ongoing, not a checkbox.** The result reads as well-organized on
   review: no mutable shared state standing in for return values, clear
   separation between components. Qualitative, and the primary bar given
   that performance is deferred.
