<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Component;

interface ShouldNotDependOnAnyComponent
{
    /** @return Where&Rules */
    public function shouldNotDependOnAnyComponent();
}
