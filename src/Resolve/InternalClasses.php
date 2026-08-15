<?php

declare(strict_types=1);

namespace Arkitect\Resolve;

/**
 * Internal in PHP's own sense (`ReflectionClass::isInternal()`): compiled
 * into the interpreter or provided by an extension, and therefore with no
 * PHP source anywhere. That covers `RuntimeException` and `ArrayObject`,
 * but equally `PDO` or `Redis` — which is why this isn't called "core".
 *
 * The one place allowed to ask the PHP runtime a question. Parsing is
 * deliberately free of runtime calls so its output is identical on every
 * machine and can be cached; this check can't be, so it lives here, in a
 * stage that is neither cached nor shared (ARCHITECTURE.md, stage 1).
 *
 * Two things depend on it. Dependency rules would otherwise flag
 * `\DateTimeImmutable` as forbidden; and ClassGraph would otherwise answer
 * Unknown for every class descending from an internal one, which is every
 * custom exception ever written.
 */
final class InternalClasses
{
    /** @var array<string, bool> */
    private array $answers = [];

    public function contains(string $fqcn): bool
    {
        return $this->answers[$fqcn] ??= $this->askTheRuntime($fqcn);
    }

    /**
     * Autoloading stays off: core symbols are always present without it, so
     * asking for one can't miss, while letting the autoloader run would
     * execute arbitrary project code just to answer a name lookup.
     */
    private function askTheRuntime(string $fqcn): bool
    {
        $known = class_exists($fqcn, false)
            || interface_exists($fqcn, false)
            || enum_exists($fqcn, false)
            || trait_exists($fqcn, false);

        if (!$known) {
            return false;
        }

        return (new \ReflectionClass($fqcn))->isInternal();
    }
}
