<?php
declare(strict_types=1);

namespace Arkitect\Rules\DSL;

use Arkitect\Expression\Expression;

interface BecauseParser
{
    public function because(string $reason): ArchRule;

    public function andShould(Expression $expression): self;
}
