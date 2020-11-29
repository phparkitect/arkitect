<?php
declare(strict_types=1);

namespace Arkitect\Rules\DSL;

interface BecauseParser
{
    public function because(string $reason): ArchRule;
}
