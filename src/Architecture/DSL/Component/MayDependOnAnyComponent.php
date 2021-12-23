<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Component;

interface MayDependOnAnyComponent
{
    /** @return Where&Rules */
    public function mayDependOnAnyComponent();
}
