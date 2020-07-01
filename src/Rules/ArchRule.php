<?php


namespace Arkitect\Rules;

class ArchRule
{
    public static function classes(): ArchRuleGivenClasses
    {
        return new ArchRuleGivenClasses();
    }

}