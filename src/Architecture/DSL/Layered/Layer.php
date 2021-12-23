<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface Layer
{
    public function layer(string $name): DefinedBy;
}
