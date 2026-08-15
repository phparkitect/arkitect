<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

final class TargetPhpVersionTest extends TestCase
{
    public function test_a_supported_version_is_accepted(): void
    {
        self::assertSame(TargetPhpVersion::Php81, TargetPhpVersion::create('8.1'));
    }

    /**
     * The value is typed by hand in a config file, so the message has to name
     * what was expected — which is what from()'s ValueError does not do.
     */
    public function test_an_unsupported_version_says_what_is_supported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid target PHP version '7.4', expected one of: 8.0, 8.1");

        TargetPhpVersion::create('7.4');
    }

    public function test_the_current_version_is_the_running_interpreter(): void
    {
        self::assertSame(\PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION, TargetPhpVersion::current()->value);
    }
}
