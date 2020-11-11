<?php
declare(strict_types=1);

namespace Arkitect\Rules;

class Rule
{
    public static function classes(): ArchRuleGivenClasses
    {
        return new ArchRuleGivenClasses();
    }
}
