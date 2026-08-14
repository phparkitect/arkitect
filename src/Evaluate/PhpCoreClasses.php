<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * The one place allowed to ask the PHP runtime a question. Parsing is
 * deliberately free of runtime calls so its output is identical on every
 * machine and can be cached; this check can't be, so it lives here, in a
 * stage that is neither cached nor shared (ARCHITECTURE.md, stage 1).
 *
 * Without it, every dependency rule would flag `\DateTimeImmutable` and
 * `\InvalidArgumentException` as forbidden dependencies.
 */
final class PhpCoreClasses
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
