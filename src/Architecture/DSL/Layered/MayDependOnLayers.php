<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface MayDependOnLayers
{
    /** @return Where&Rules */
    public function mayDependOnLayers(string ...$layerNames);
}
