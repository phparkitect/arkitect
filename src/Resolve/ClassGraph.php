<?php

declare(strict_types=1);

namespace Arkitect\Resolve;

/**
 * The questions rules ask about types. Separate from how they are answered:
 * the parsed set is one way, and the only one today.
 */
interface ClassGraph
{
    public function isA(string $fqcn, string $target): Membership;

    /** Follows the `extends` chain only, and is not reflexive. */
    public function hasAncestor(string $fqcn, string $target): Membership;
}
