<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Exceptions\InvalidPatternException;

/**
 * Only '*' and '?' are wildcards: a regex is rejected rather than matched
 * literally, so a stray '.' is reported instead of silently never matching.
 */
class Pattern
{
    private const VALID_PATTERN = '/^([a-zA-Z0-9_\x80-\xff]|\\\\|\*|\?)*$/';

    /**
     * The same handful of patterns is matched against every class in the
     * codebase, so they are parsed once and shared.
     *
     * @var array<string, self>
     */
    private static array $parsed = [];

    private string $pattern;

    private bool $hasWildcard;

    private function __construct(string $pattern)
    {
        $this->pattern = $pattern;
        $this->hasWildcard = str_contains($pattern, '*') || str_contains($pattern, '?');
    }

    public static function fromString(string $pattern): self
    {
        if (isset(self::$parsed[$pattern])) {
            return self::$parsed[$pattern];
        }

        if (0 === preg_match(self::VALID_PATTERN, $pattern)) {
            throw new InvalidPatternException("'$pattern' is not a valid class or namespace pattern. Regex are not allowed, only * and ? wildcard.");
        }

        // a trailing separator denotes the same namespace: 'App\Foo\' is 'App\Foo'
        $named = rtrim($pattern, '\\');

        if ('' === $named) {
            throw new InvalidPatternException("'$pattern' names no class and no namespace. Use '*' to mean every class.");
        }

        return self::$parsed[$pattern] = new self($named);
    }

    /** Matches one whole name: 'App\Foo' does not match 'App\Foo\Bar'. */
    public function matches(string $subject): bool
    {
        if (!$this->hasWildcard) {
            return $this->pattern === $subject;
        }

        return fnmatch($this->pattern, $subject, \FNM_NOESCAPE);
    }

    public function toString(): string
    {
        return $this->pattern;
    }
}
