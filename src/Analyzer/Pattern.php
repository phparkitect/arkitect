<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Exceptions\InvalidPatternException;

/**
 * A class or namespace pattern, as the user writes it in a rule.
 *
 * Only '*' and '?' are wildcards: a regex is rejected instead of being matched
 * literally, so that a stray '.' is reported as a mistake rather than silently
 * never matching.
 */
class Pattern
{
    private const VALID_PATTERN = '/^([a-zA-Z0-9_\x80-\xff]|\\\\|\*|\?)*$/';

    /**
     * Patterns come from the rules, so the same handful of them is matched
     * against every class in the codebase: they are parsed once and shared.
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
        return self::$parsed[$pattern] = new self(rtrim($pattern, '\\'));
    }

    /**
     * Whether the pattern denotes exactly this name, end to end.
     *
     * This is a match on one name: it says nothing about what the name contains,
     * so 'App\Foo' does not match the class 'App\Foo\Bar'. Containment is asked
     * of the name itself, through FullyQualifiedClassName::matches().
     */
    public function matches(string $subject): bool
    {
        if ('' === $this->pattern) {
            return false;
        }

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
