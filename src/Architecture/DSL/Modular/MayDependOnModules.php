<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Modular;

interface MayDependOnModules
{
    /** @return Where&Rules */
    public function mayDependOnModules(string ...$moduleNames);
}
