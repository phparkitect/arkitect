<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures\HappyIsland\OtherBadCode;

use Arkitect\Tests\E2E\Fixtures\HappyIsland\HappyIslandSub\HappyClass;

class OtherBadCode
{
    private $happy;

    public function __construct(HappyClass $happy)
    {
        $this->happy = $happy;
    }
}
