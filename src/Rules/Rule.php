<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\AndThatShouldParser;

class Rule
{
    public static function allClasses(): AllClasses
    {
        return new AllClasses();
    }

    public static function namespace(string ...$namespaces): AndThatShouldParser
    {
        return self::allClasses()->that(new ResideInOneOfTheseNamespaces(...$namespaces));
    }
}
