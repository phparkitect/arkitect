<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures\HappyIsland\BadCode;

class BadCode
{
    private $happy;

    public function __construct(HappyClass $happy)
    {
        $this->happy = $happy;
    }
}
