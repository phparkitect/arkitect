<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Modular;

interface DefinedBy
{
    /** @return Module&Where */
    public function definedBy(string $selector);
}
