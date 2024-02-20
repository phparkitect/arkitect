<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface ShouldOnlyDependOnComponents
{
    /**
     * May depend ONLY on the specified components, thus it can only depend on itself if itself is specified.
     *
     * @param string[] $componentNames
     *
     * @return Where&Rules
     */
    public function shouldOnlyDependOnComponents(string ...$componentNames);
}
