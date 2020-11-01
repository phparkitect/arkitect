<?php
declare(strict_types=1);

namespace App\BadCode;

class BadCode
{
    private $happy;

    public function __construct(HappyClass $happy)
    {
        $this->happy = $happy;
    }
}
