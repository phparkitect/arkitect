<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\Implements;

enum AEnum implements AnInterface
{
    case PENDING;
    case PAID;

    public function amethod()
    {
    }
}
