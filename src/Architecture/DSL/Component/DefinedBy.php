<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Component;

interface DefinedBy
{
    /** @return Component&Where */
    public function definedBy(string $selector);
}
