<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface ShouldOnlyDependOnComponents
{
    /** @return Where&Rules */
    public function shouldOnlyDependOnComponents(string ...$componentNames);
}
