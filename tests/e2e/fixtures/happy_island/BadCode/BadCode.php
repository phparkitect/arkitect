<?php


namespace App\BadCode;


class BadCode
{
    private $happy;

    public function __construct(HappyClass $happy)
    {
        $this->happy = $happy;
    }
}