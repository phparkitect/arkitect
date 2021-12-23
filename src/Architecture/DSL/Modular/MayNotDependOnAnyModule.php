<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Modular;

interface MayNotDependOnAnyModule
{
    /** @return Where&Rules */
    public function mayNotDependOnAnyModule();
}
