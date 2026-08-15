<?php

declare(strict_types=1);

namespace Arkitect\Resolve;

use Arkitect\Parser\ParsedClass;

/**
 * Answers from the parsed set alone, except for one thing it cannot: a name
 * missing from that set may be internal, and an internal class has no
 * source to parse. That is the only reach for the runtime, and keeping it in
 * an implementation is why the questions live in an interface.
 */
final class ParsedClassGraph implements ClassGraph
{
    /** @var array<string, ParsedClass> */
    private array $byFqcn;

    private readonly InternalClasses $internal;

    public function __construct(ParsedClass ...$classes)
    {
        $this->byFqcn = [];
        foreach ($classes as $class) {
            $this->byFqcn[$class->fqcn] = $class;
        }

        $this->internal = new InternalClasses();
    }

    public function isA(string $fqcn, string $target): Membership
    {
        if ($fqcn === $target) {
            return Membership::Yes;
        }

        $class = $this->byFqcn[$fqcn] ?? null;

        if (null === $class) {
            return $this->outsideTheParsedSet($fqcn, $target, static fn (string $f, string $t) => is_a($f, $t, true));
        }

        $anyUnknown = false;

        foreach ([...$class->extends, ...$class->implements] as $parent) {
            $result = $this->isA($parent->name, $target);

            if (Membership::Yes === $result) {
                return Membership::Yes;
            }

            if (Membership::Unknown === $result) {
                $anyUnknown = true;
            }
        }

        return $anyUnknown ? Membership::Unknown : Membership::No;
    }

    /**
     * Follows the `extends` chain only, and is not reflexive: a class is a
     * subtype of itself but does not extend itself. A declared parent
     * matches by name before the walk continues into it, so extending a
     * class that was never parsed still answers Yes.
     */
    public function hasAncestor(string $fqcn, string $target): Membership
    {
        $class = $this->byFqcn[$fqcn] ?? null;

        if (null === $class) {
            return $this->outsideTheParsedSet($fqcn, $target, self::extendsChainOf(...));
        }

        $anyUnknown = false;

        foreach ($class->extends as $parent) {
            if ($parent->name === $target) {
                return Membership::Yes;
            }

            $result = $this->hasAncestor($parent->name, $target);

            if (Membership::Yes === $result) {
                return Membership::Yes;
            }

            if (Membership::Unknown === $result) {
                $anyUnknown = true;
            }
        }

        return $anyUnknown ? Membership::Unknown : Membership::No;
    }

    /**
     * A name the parsed set doesn't contain. Internal classes are never in
     * it and never can be — they have no PHP source — so treating them as
     * Unknown would make every descendant of an exception unanswerable.
     * They resolve instead: an internal class only ever inherits from other
     * internal classes, since no extension can name a user-defined type, so
     * a user-defined target is unreachable through one. An internal target
     * is a question the runtime can answer, and it is the same answer on
     * every machine that loaded the same extensions.
     *
     * Anything else is genuinely unknown: it has source somewhere, and that
     * source wasn't in what we parsed.
     *
     * @param callable(string, string): bool $reaches
     */
    private function outsideTheParsedSet(string $fqcn, string $target, callable $reaches): Membership
    {
        if (!$this->internal->contains($fqcn)) {
            return Membership::Unknown;
        }

        if (!$this->internal->contains($target)) {
            return Membership::No;
        }

        return $reaches($fqcn, $target) ? Membership::Yes : Membership::No;
    }

    /** Whether $target is in $fqcn's `extends` chain, interfaces excluded. */
    private static function extendsChainOf(string $fqcn, string $target): bool
    {
        $ancestors = class_parents($fqcn) ?: [];

        if ([] === $ancestors && interface_exists($fqcn, false)) {
            // an interface's `extends` is what class_implements reports
            $ancestors = class_implements($fqcn) ?: [];
        }

        return isset($ancestors[$target]);
    }
}
