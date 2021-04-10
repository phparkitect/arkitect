<?php
declare(strict_types=1);

namespace Arkitect\Expression;

class PositiveDescription implements Description
{
    /** @var string */
    private $description;

    public function __construct(string $description)
    {
        $this->description = $description;
    }

    public function toString(): string
    {
        return $this->toPositive();
    }

    public function getPattern(): string
    {
        return $this->description;
    }

    protected function toPositive(): string
    {
        // looking for the pattern [positive | negative] in the string and take the positive
        return preg_replace('/\[(.+)\|(.+)]/i', '$1', $this->description);
    }
}
