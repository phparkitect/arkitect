<?php

declare(strict_types=1);

namespace Arkitect\Parser;

final class ParsingError
{
    public function __construct(
        public readonly string $filePath,
        public readonly string $message,
    ) {
    }
}
