<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures\HappyIsland\HappyIslandSub;

use Arkitect\Tests\E2E\Fixtures\HappyIsland\BadCode\BadCode;

class HappyClass
{
    /**
     * @var BadCode
     */
    private $bad;

    public function __construct(BadCode $bad)
    {
        $this->bad = $bad;
    }
}
