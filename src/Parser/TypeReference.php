<?php

declare(strict_types=1);

namespace Arkitect\Parser;

final class TypeReference
{
    public function __construct(
        public readonly string $name,
        public readonly int $line,
    ) {
    }
}
