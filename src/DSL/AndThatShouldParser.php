<?php
declare(strict_types=1);

namespace Arkitect\DSL;

use Arkitect\Expression\Expression;

interface AndThatShouldParser
{
    public function andThat(Expression $expression): self;

    public function should(Expression $expression): BecauseParser;
}
