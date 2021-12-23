<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Component;

interface Component
{
    public function component(string $name): DefinedBy;
}
