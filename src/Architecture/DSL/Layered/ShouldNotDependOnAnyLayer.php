<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface ShouldNotDependOnAnyLayer
{
    /** @return Where&Rules */
    public function shouldNotDependOnAnyLayer();
}
