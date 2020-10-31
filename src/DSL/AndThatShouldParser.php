<?php
declare(strict_types=1);

namespace Arkitect\DSL;

interface AndThatShouldParser
{
    public function andThat(Expression $expression): AndThatShouldParser;

    public function should(Expression $expression): BecauseParser;
}
