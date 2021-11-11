<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class PatternString
{
    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function matches(string $pattern): bool
    {
        if ('' === $pattern) {
            return false;
        }

        if (!$this->containsWildcard($pattern) && str_starts_with($this->value, $pattern)) {
            return true;
        }

        return (boolean)(preg_match('#^' . $this->convertShellToRegExPattern($pattern) . '#', $this->value)===1);
    }

    private function convertShellToRegExPattern(string $pattern): string
    {
        return strtr($pattern, [
            '*' => '.*',
            '?' => '.',
            '.' => '\.',
            '[!' => '[^',
            '\\' => '\\\\',
        ]);
    }

    public function explode(string $delimiter): array
    {
        return explode($delimiter, $this->value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    private function containsWildcard(string $pattern): bool
    {
        return
            str_contains($pattern, '*') ||
            str_contains($pattern, '?') ||
            str_contains($pattern, '.') ||
            str_contains($pattern, '[');
    }
}
