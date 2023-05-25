<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Exceptions\InvalidPatternException;

class FullyQualifiedClassName
{
    /** @var PatternString */
    private $fqcnString;

    /** @var PatternString */
    private $namespace;

    /** @var PatternString */
    private $class;

    private function __construct(PatternString $fqcnString, PatternString $namespace, PatternString $class)
    {
        $this->fqcnString = $fqcnString;
        $this->namespace = $namespace;
        $this->class = $class;
    }

    public function toString(): string
    {
        return $this->fqcnString->toString();
    }

    public function classMatches(string $pattern): bool
    {
        if ($this->isNotAValidPattern($pattern)) {
            throw new InvalidPatternException("'$pattern' is not a valid class or namespace pattern. Regex are not allowed, only * and ? wildcard.");
        }

        return $this->class->matches($pattern);
    }

    public function matches(string $pattern): bool
    {
        if ($this->isNotAValidPattern($pattern)) {
            throw new InvalidPatternException("'$pattern' is not a valid class or namespace pattern. Regex are not allowed, only * and ? wildcard.");
        }

        return $this->fqcnString->matches($pattern);
    }

    public function className(): string
    {
        return $this->class->toString();
    }

    public function namespace(): string
    {
        return $this->namespace->toString();
    }

    public static function fromString(string $fqcn): self
    {
        $validFqcn = '/^[a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]$/';

        if (!(bool) preg_match($validFqcn, $fqcn)) {
            throw new \RuntimeException("$fqcn is not a valid namespace definition");
        }

        $pieces = explode('\\', $fqcn);
        $piecesWithoutEmpty = array_filter($pieces);
        $className = array_pop($piecesWithoutEmpty);
        $namespace = implode('\\', $piecesWithoutEmpty);

        return new self(new PatternString($fqcn), new PatternString($namespace), new PatternString($className));
    }

    public function isNotAValidPattern(string $pattern): bool
    {
        $validClassNameCharacted = '[a-zA-Z0-9_\x80-\xff]';
        $or = '|';
        $backslash = '\\\\';

        return 0 === preg_match('/^('.$validClassNameCharacted.$or.$backslash.$or.'\*'.$or.'\?)*$/', $pattern);
    }
}
