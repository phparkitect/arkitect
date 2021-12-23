<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Modular;

interface Module
{
    public function module(string $name): DefinedBy;
}
