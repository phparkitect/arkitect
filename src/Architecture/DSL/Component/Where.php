<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Component;

interface Where
{
    /** @return ShouldNotDependOnAnyComponent&MayDependOnComponents&MayDependOnAnyComponent */
    public function where(string $componentName);
}
