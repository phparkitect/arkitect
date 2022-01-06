<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface MayDependOnAnyComponent
{
    /** @return Where&Rules */
    public function mayDependOnAnyComponent();
}
