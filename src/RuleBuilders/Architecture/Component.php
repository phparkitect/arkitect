<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

interface Component
{
    public function component(string $name): DefinedBy;
}
