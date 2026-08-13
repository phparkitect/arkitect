# PHPArkitect — Design Philosophy

There are already capable tools for enforcing architecture in PHP. PHPArkitect is not trying to be the most powerful one. It's trying to be **the one you don't have to study** — the one where you write a rule the way you'd say it out loud, and it does exactly what you expect. Every decision below serves that goal.

## You shouldn't have to learn the tool to use it

The whole point is approachability. Rules are written in **plain PHP** on purpose: your IDE's autocomplete should guide you through composing a rule, so the API teaches itself as you type. The hard parts — parsing source into an AST, resolving dependencies, handling every corner of valid PHP — are the tool's job. You stay focused on the only thing you actually care about: **stating your rules.**

## Readable and predictable beats clever

Rules should read **almost like English**. We value composability — reusing a rule across folders, namespaces, sets — but we value **readability and predictability more**. When the two conflict, the readable, predictable option wins, every time. A rule you can't read at a glance is a rule nobody trusts.

## Explicit, never ambiguous

A rule must mean one thing. If a feature would make rules ambiguous — where the reader can't be sure what will pass or fail — we'd **rather not have that feature at all**. Missing a capability is a known limitation; an ambiguous one is a trap.

## Small on purpose

An approachable tool has to stay small. Every superfluous part adds learning cost without adding value. So **every feature must solve a specific, real use case** — we don't add things that *might* be useful someday. Ideally there is **one way to do each thing**, so users are never slowed down by choosing between options. (The one deliberate exception: similar expressions with different focus, when each makes a rule read more naturally — expressiveness, not redundancy.)

## Reliable first, fast second

Performance is part of the experience: a tool that runs in CI must be fast. But **being reliable and predictable matters more than being fast.** A result you can't trust is worse than a result you wait for — a cache that's occasionally wrong, for instance, does more damage than a tool that's simply slower. We optimize speed only after correctness and consistency are guaranteed — never the other way around.

## Real-world stability, without standing still

This tool runs in thousands of real projects. Consistency matters and we don't break it lightly. But consistency must not stop us from offering a better experience — when we improve things, we do it **honoring semantic versioning**, so upgrades are never a surprise.

## Documentation is a feature

If the goal is to be the most approachable tool, then **concise, effective documentation is a first-class part of the product**, not an afterthought. Docs that are short and clear are worth more than docs that are exhaustive and unread.

## The config communicates architectural intent

A `phparkitect.php` file is not just machine input — it's how a team **writes down its architectural decisions**. That's why `because(...)` exists and why it matters: the reason you give is human communication, read by the next developer who hits the rule. We design the DSL so that configuration doubles as documentation of *why* the architecture is the way it is.

## Extensible over bloated

Some rules make perfect sense for one project and none for everyone else. That's expected — and it's exactly why extensibility matters more than a big built-in catalog. **Writing a custom rule must be practical and well documented**: implementing an `Expression` is a first-class, supported path, not a workaround. Keeping the core small and making extension easy are two sides of the same decision, so a niche need is never a reason to grow the core — an extensible tool stays approachable in a way a bloated one never can.
