<?php
declare(strict_types=1);

namespace Arkitect\Rules;

class Rule
{
    public static function allClasses(): AllClasses
    {
        return new AllClasses();
    }
}
