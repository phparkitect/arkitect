<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface Where
{
    /** @return ShouldNotDependOnAnyComponent&ShouldOnlyDependOnComponents&MayDependOnComponents&MayDependOnAnyComponent */
    public function where(string $componentName);
}
