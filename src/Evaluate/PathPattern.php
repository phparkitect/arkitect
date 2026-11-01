<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * A pattern over a file path, relative to the project root and written with
 * `/`. The wildcards are the ones `Pattern` uses — `*` stands for part of
 * one segment and never crosses a separator, `**` written as a whole
 * segment stands for any number of segments, none included — so a rule
 * reads the same whether it talks about namespaces or about directories.
 *
 * Separate from `Pattern` because a path is not a name: it carries an
 * extension, `.` and `-` are ordinary characters in it, and its separator
 * is `/`.
 */
final class PathPattern
{
    private const ALLOWED = '/^([a-zA-Z0-9_\x80-\xff.\-]|\/|\*)+$/';

    private readonly string $value;

    private readonly string $regex;

    public function __construct(string $value)
    {
        // paths from the parser are relative to the root and carry no
        // separator at either end, so the same holds for what they are
        // matched against: '/src/' and 'src' have to be one pattern
        $value = trim($value, '/');
        $this->value = $value;

        if (1 !== preg_match(self::ALLOWED, $value)) {
            throw new \InvalidArgumentException(\sprintf("'%s' is not a valid path pattern: only * and ** are wildcards.", $value));
        }

        $segments = explode('/', $value);

        foreach ($segments as $segment) {
            if ('**' !== $segment && str_contains($segment, '**')) {
                throw new \InvalidArgumentException(\sprintf("'%s' is not a valid path pattern: ** stands for whole segments, as in src/**/Domain.", $value));
            }

            // a relative step can never occur in a root-relative path, so a
            // pattern carrying one would match nothing without saying why
            if ('.' === $segment || '..' === $segment) {
                throw new \InvalidArgumentException(\sprintf("'%s' is not a valid path pattern: it is matched against paths relative to the project root, which contain no '%s' step.", $value, $segment));
            }
        }

        $this->regex = implode('', array_map(self::segmentRegex(...), $segments));
    }

    /** Whether the file at $path sits in the directory written, or beneath it. */
    public function contains(string $path): bool
    {
        return 1 === preg_match('/^'.$this->regex.'(\/.*)?$/', self::separated(self::directoryOf($path)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    /** Empty for a file sitting at the root. */
    private static function directoryOf(string $path): string
    {
        $lastSeparator = strrpos($path, '/');

        return false === $lastSeparator ? '' : substr($path, 0, $lastSeparator);
    }

    /**
     * Every segment carries the separator in front of it, and so does the
     * path it is matched against: that is what lets `**` stand for no
     * segments at all without leaving a separator behind.
     */
    private static function segmentRegex(string $segment): string
    {
        if ('**' === $segment) {
            return '(\/[^\/]+)*';
        }

        return '\/'.str_replace('\*', '[^\/]*', preg_quote($segment, '/'));
    }

    private static function separated(string $path): string
    {
        return '' === $path ? '' : '/'.$path;
    }
}
