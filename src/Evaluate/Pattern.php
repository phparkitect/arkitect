<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\Fqcn;

/**
 * A class or namespace pattern. `*` stands for part of one segment and
 * never crosses a separator; `**`, written as a whole segment, stands for
 * any number of segments, none included. Nothing else is a wildcard, and a
 * regex is rejected at construction rather than halfway through a run.
 *
 * The same string asks two different questions depending on the rule:
 * matches() whether a name is the one written, contains() whether a name
 * is declared in the namespace written or beneath it. A rule about
 * namespaces only ever asks the second, so the class `App\Domain` is not
 * in the namespace `App\Domain`.
 */
final class Pattern
{
    private const ALLOWED = '/^([a-zA-Z0-9_\x80-\xff]|\\\\|\*)+$/';

    private readonly string $value;

    private readonly string $regex;

    public function __construct(string $value)
    {
        // a pattern can't be an Fqcn — it has wildcards — but it is written
        // against names that never carry a leading separator, so the same
        // normalization applies or `\App\Domain` would match nothing at all
        $value = str_starts_with($value, '\\') ? substr($value, 1) : $value;
        $this->value = $value;

        if (1 !== preg_match(self::ALLOWED, $value)) {
            throw new \InvalidArgumentException(\sprintf("'%s' is not a valid class or namespace pattern: only * and ** are wildcards.", $value));
        }

        $segments = explode('\\', rtrim($value, '\\'));

        foreach ($segments as $segment) {
            if ('**' !== $segment && str_contains($segment, '**')) {
                throw new \InvalidArgumentException(\sprintf("'%s' is not a valid class or namespace pattern: ** stands for whole segments, as in App\\**\\Domain.", $value));
            }
        }

        $this->regex = implode('', array_map(self::segmentRegex(...), $segments));
    }

    public function matches(string $name): bool
    {
        return 1 === preg_match('/^'.$this->regex.'$/', self::separated($name));
    }

    public function contains(string $name): bool
    {
        $namespace = (new Fqcn($name))->namespaceName();

        return 1 === preg_match('/^'.$this->regex.'(\\\\.*)?$/', self::separated($namespace));
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Every segment carries the separator in front of it, and so does the
     * name it is matched against: that is what lets `**` stand for no
     * segments at all without leaving a separator behind.
     */
    private static function segmentRegex(string $segment): string
    {
        if ('**' === $segment) {
            return '(\\\\[^\\\\]+)*';
        }

        return '\\\\'.str_replace('\*', '[^\\\\]*', preg_quote($segment, '/'));
    }

    private static function separated(string $name): string
    {
        return '' === $name ? '' : '\\'.$name;
    }
}
