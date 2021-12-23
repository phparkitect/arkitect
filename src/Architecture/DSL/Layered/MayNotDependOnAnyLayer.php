<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface MayNotDependOnAnyLayer
{
    /** @return Where&Rules */
    public function mayNotDependOnAnyLayer();
}
