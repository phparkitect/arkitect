<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions;

use Arkitect\Expression\Description;
use PHPUnit\Framework\TestCase;

class DescriptionTest extends TestCase
{
    public function test_it_should_return_description(): void
    {
        $description = new Description('an example', '');
        self::assertEquals('an example', $description->toString());
    }

    public function test_it_should_append_because_when_is_present(): void
    {
        $description = new Description('an example', 'reasons');
        self::assertEquals('an example because reasons', $description->toString());
    }
}
