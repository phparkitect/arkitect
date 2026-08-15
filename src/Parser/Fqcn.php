<?php

declare(strict_types=1);

namespace Arkitect\Parser;

/**
 * A fully qualified class name, in the one spelling this codebase uses: no
 * leading separator. That is the form php-parser's `toString()` produces,
 * the form `ParsedClass::$fqcn` carries, and the form `Foo::class` returns
 * — the leading `\` belongs to source syntax, not to the name itself.
 *
 * A leading separator is normalized away rather than rejected: `\App\Foo`
 * and `App\Foo` name the same class beyond any ambiguity, and people write
 * the first when copying from code. Refusing it would be pedantry; keeping
 * both would be worse than either, because `ClassGraph` indexes on this
 * string and the two spellings would become two unrelated types.
 */
final class Fqcn
{
    private const VALID = '/^[a-zA-Z0-9_\x80-\xff]+(\\\\[a-zA-Z0-9_\x80-\xff]+)*$/';

    public readonly string $value;

    public function __construct(string $value)
    {
        // exactly one, so `\\App\Foo` still fails: it is not a name anyone meant
        $normalized = str_starts_with($value, '\\') ? substr($value, 1) : $value;

        if (1 !== preg_match(self::VALID, $normalized)) {
            throw new \InvalidArgumentException(\sprintf("'%s' is not a fully qualified class name.", $value));
        }

        $this->value = $normalized;
    }

    public function toString(): string
    {
        return $this->value;
    }

    /** The declared name without its namespace. */
    public function shortName(): string
    {
        $lastSeparator = strrpos($this->value, '\\');

        return false === $lastSeparator ? $this->value : substr($this->value, $lastSeparator + 1);
    }

    /** Empty for a class declared in the global namespace. */
    public function namespaceName(): string
    {
        $lastSeparator = strrpos($this->value, '\\');

        return false === $lastSeparator ? '' : substr($this->value, 0, $lastSeparator);
    }
}
