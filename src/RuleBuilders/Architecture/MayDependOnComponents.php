<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface MayDependOnComponents
{
    /** @return Where&Rules */
    public function mayDependOnComponents(string ...$componentNames);
}
