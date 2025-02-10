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
        $targetPhpVersion = TargetPhpVersion::create('8.3');

        $this->assertEquals('8.3', $targetPhpVersion->get());
    }

    public function test_it_should_ignore_the_patch_number(): void
    {
        $targetPhpVersion = TargetPhpVersion::create('8.3.2');

        $this->assertEquals('8.3', $targetPhpVersion->get());
    }

    public function test_it_should_ignore_extra_informations(): void
    {
        $targetPhpVersion = TargetPhpVersion::create('7.4.10-14+ubuntu22.04.1+deb.sury.org+1');

        $this->assertEquals('7.4', $targetPhpVersion->get());
    }

    public function test_it_should_throw_exception_if_not_valid_php_version(): void
    {
        $this->expectException(PhpVersionNotValidException::class);
        $this->expectExceptionMessage('PHP version not valid for PHPArkitect parser WRONG');
        TargetPhpVersion::create('WRONG');
    }
}
