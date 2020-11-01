<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class HaveNameMatching implements Expression
{
    /**
     * @var string
     */
    private $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function evaluate(ClassDescription $class): bool
    {
        return $class->nameMatches($this->pattern);
    }

    public function toString(): string
    {
        return sprintf('have name matching %s', $this->pattern);
    }
}
