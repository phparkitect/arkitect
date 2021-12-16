<?php

declare(strict_types=1);

namespace Arkitect\Rules;

class RuleException
{
    /** @var string[] */
    private $fullyQualifiedClassNames;

    public function __construct(string ...$fullyQualifiedClassNames)
    {
        $this->fullyQualifiedClassNames = $fullyQualifiedClassNames;
    }

    public static function create(string $fullyQualifiedClassNames): self
    {
        return new self(...$fullyQualifiedClassNames);
    }

    public function getFullyQualifiedClassNames(): array
    {
        return $this->fullyQualifiedClassNames;
    }

    public function isAllowed(string $fullyQualifiedClassName): bool
    {
        return !in_array($fullyQualifiedClassName, $this->fullyQualifiedClassNames);
    }
}
