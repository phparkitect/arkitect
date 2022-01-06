<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface DefinedBy
{
    /** @return Component&Where */
    public function definedBy(string $selector);
}
