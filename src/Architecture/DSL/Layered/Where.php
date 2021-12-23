<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface Where
{
    /** @return ShouldNotDependOnAnyLayer&MayDependOnLayers&MayDependOnAnyLayer */
    public function where(string $layerName);
}
