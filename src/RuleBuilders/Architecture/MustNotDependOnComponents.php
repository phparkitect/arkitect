<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface MustNotDependOnComponents
{
    /**
     * This allows us to specify
     * "this component can not depend on these components, but everything else is allowed (unless otherwise specified by another rule)".
     *
     * @param string[] $componentNames
     *
     * @return Where&Rules
     */
    public function mustNotDependOnComponents(string ...$componentNames);
}
