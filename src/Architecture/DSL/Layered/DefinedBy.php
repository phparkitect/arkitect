<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface DefinedBy
{
    /** @return Layer&Where */
    public function definedBy(string $selector);
}
