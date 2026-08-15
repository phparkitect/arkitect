<?php

declare(strict_types=1);

namespace Arkitect\Parser;

/**
 * One occurrence of a type name in a file — the name, and where it was
 * written. The line is not incidental: without it this would just be a
 * name, and it is what lets a violation point at the dependency itself
 * instead of at the class declaration.
 *
 * The name is fully qualified and carries no leading separator. That isn't
 * a style preference: `ClassGraph` indexes classes by this string, so
 * accepting both `App\Foo` and `\App\Foo` would silently make them two
 * different types.
 */
final class TypeReference
{
    public readonly string $name;

    public function __construct(
        string $name,
        public readonly int $line,
    ) {
        $this->name = (new Fqcn($name))->toString();

        if ($line < 1) {
            throw new \InvalidArgumentException(\sprintf("'%s' was given line %d: a reference exists somewhere in a file, and every violation reports a line.", $name, $line));
        }
    }
}
