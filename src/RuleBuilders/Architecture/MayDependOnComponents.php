<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface MayDependOnComponents
{
    /**
     * May depend on the specified components, plus itself.
     *
     * @param array<string> $componentNames
     *
     * @return Where&Rules
     */
    public function mayDependOnComponents(string ...$componentNames);
}
