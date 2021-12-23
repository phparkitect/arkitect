<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Modular;

interface ShouldNotDependOnAnyModule
{
    /** @return Where&Rules */
    public function shouldNotDependOnAnyModule();
}
