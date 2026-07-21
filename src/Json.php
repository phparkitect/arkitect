<?php
declare(strict_types=1);

namespace Arkitect;

class Json
{
    public static function encode(mixed $value): string
    {
        return json_encode(
            $value,
            // throw instead of silently returning false, e.g. on unsupported types or exceeded depth
            \JSON_THROW_ON_ERROR
            // substitute invalid UTF-8 byte sequences instead of failing, since we may encode
            // arbitrary strings extracted from source files we don't control
            | \JSON_INVALID_UTF8_SUBSTITUTE
            | \JSON_PRETTY_PRINT
        );
    }
}
