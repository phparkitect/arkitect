<?php
declare(strict_types=1);

namespace App\HappyIsland;

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
