<?php
declare(strict_types=1);

namespace App\BadCode;

class OtherBadCode
{
    private $happy;

    public function __construct(HappyClass $happy)
    {
        $this->happy = $happy;
    }
}
