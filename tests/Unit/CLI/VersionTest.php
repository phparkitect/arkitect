<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function test_it_should_return_version(): void
    {
        $this->assertEquals('0.3.14', Version::get());
    }
}
