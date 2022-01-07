<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface ShouldNotDependOnAnyComponent
{
    /** @return Where&Rules */
    public function shouldNotDependOnAnyComponent();
}
