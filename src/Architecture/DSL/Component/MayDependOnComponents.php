<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Component;

interface MayDependOnComponents
{
    /** @return Where&Rules */
    public function mayDependOnComponents(string ...$componentNames);
}
