<?php

declare(strict_types=1);

namespace Arkitect\Shared\String;

final class IndentationHelper
{
    public static function indent(string $text, int $spaces = 2): string
    {
        return preg_replace('/^/m', str_repeat(' ', $spaces), $text);
    }

    public static function clearEmptyLines(string $text): string
    {
        return preg_replace('/^\s+$/m', '', $text);
    }
}
