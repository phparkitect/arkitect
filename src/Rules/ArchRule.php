<?php
declare(strict_types=1);


namespace Arkitect\Rules;

class ArchRule
{
    public static function classes(): ArchRuleGivenClasses
    {
        return new ArchRuleGivenClasses();
    }
}
