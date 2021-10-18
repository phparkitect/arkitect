<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Exceptions\PhpVersionNotValidException;
use PHPUnit\Framework\TestCase;

class TargetPhpVersionTest extends TestCase
{
    public function test_it_should_return_passed_php_version(): void
    {
        $targetPhpVersion = TargetPhpVersion::create('7.4');

        $this->assertEquals('7.4', $targetPhpVersion->get());
    }

    public function test_it_should_throw_exception_if_not_valid_php_version(): void
    {
        $this->expectException(PhpVersionNotValidException::class);
        $this->expectExceptionMessage('PHP version not valid for parser foo');
        TargetPhpVersion::create('foo');
    }
}
