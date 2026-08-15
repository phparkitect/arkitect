<?php

declare(strict_types=1);

namespace Arkitect\Resolve;

/**
 * Internal in PHP's own sense (`ReflectionClass::isInternal()`): no PHP
 * source anywhere, which covers extension classes like `PDO` as well as
 * `RuntimeException` — hence not "core".
 *
 * The one place allowed to ask the PHP runtime a question. Keep it out of
 * parsing, whose output has to be identical on every machine to be
 * cacheable.
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
