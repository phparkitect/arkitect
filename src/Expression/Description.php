<?php

declare(strict_types=1);

namespace Arkitect\Expression;

class Description
{
    private string $description;

    public function __construct(string $description, string $because)
    {
        $this->description = $description;

        if ('' !== $because) {
            $this->description .= ' because '.$because;
        }
    }

    public function toString(): string
    {
        return $this->description;
    }
}
