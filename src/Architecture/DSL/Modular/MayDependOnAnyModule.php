<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Modular;

interface MayDependOnAnyModule
{
    /** @return Where&Rules */
    public function mayDependOnAnyModule();
}
