<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Modular;

interface Where
{
    /** @return ShouldNotDependOnAnyModule&MayDependOnModules&MayDependOnAnyModule */
    public function where(string $moduleName);
}
