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

        return $this->startsWithPattern($pattern);
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

    private function startsWithPattern(string $pattern): bool
    {
        return 1 === preg_match('#^'.$this->convertShellToRegExPattern($pattern).'#', $this->value);
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
}
