<?php
declare(strict_types=1);

namespace Arkitect;

class Glob
{
    public static function toRegex($glob): string
    {
        $regexp = strtr(preg_quote($glob, '/'), [
            '\*' => '.*',
            '\?' => '.',
            '\[' => '[',
            '\]' => ']',
            '\[\!' => '[ˆ',
        ]);

        return '/'.$regexp.'/';
    }
}
