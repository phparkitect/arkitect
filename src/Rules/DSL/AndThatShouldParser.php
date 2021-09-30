<?php
declare(strict_types=1);

namespace Arkitect\Rules\DSL;

use Arkitect\Expression\Expression;

interface AndThatShouldParser
{
    public function andThat(Expression $expression): self;

    public function should(Expression $expression): BecauseParser;
}
