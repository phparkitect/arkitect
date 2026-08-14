<?php

declare(strict_types=1);

namespace Arkitect\Parser;

final class ParseResult
{
    /**
     * @param list<ParsedClass>   $classes
     * @param list<ParsingError>  $errors
     */
    public function __construct(
        public readonly array $classes,
        public readonly array $errors,
    ) {
    }
}
