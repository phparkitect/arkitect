<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface MayDependOnAnyLayer
{
    /** @return Where&Rules */
    public function mayDependOnAnyLayer();
}
