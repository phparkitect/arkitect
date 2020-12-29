<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\Glob;
use PHPUnit\Framework\TestCase;

class GlobTest extends TestCase
{
    public function test_can_exclude_using_glob_pattern(): void
    {
        // * - Matches zero or more characters.
        $this->assertEquals('/.*Catalog.*/', Glob::toRegex('*Catalog*'));

        // * - Matches zero or more characters.
        $this->assertEquals('/Cata\.log/', Glob::toRegex('Cata.log'));

        // ? - Matches exactly one character (any character).
        $this->assertEquals('/C.talog/', Glob::toRegex('C?talog'));

        // [...] - Matches one character from a group of characters. If the first character is !, matches any character not in the group.
        $this->assertEquals('/prova[123]/', Glob::toRegex('prova[123]'));

        // [...] - Matches one character from a group of characters. If the first character is !, matches any character not in the group.
        $this->assertEquals('/prova[ˆ123]/', Glob::toRegex('prova[!123]'));
    }
}
