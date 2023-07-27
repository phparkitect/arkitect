<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

use Arkitect\Expression\Expression;

interface DefinedBy
{
    /** @return Component&Where */
    public function definedBy(string $selector);

    /** @return Component&Where */
    public function definedByExpression(Expression $selector);
}
