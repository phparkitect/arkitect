<?php
declare(strict_types=1);

namespace Arkitect\Rules\DSL;

use Arkitect\Expression\Expression;
use Arkitect\Rules\RuleException;

interface AndThatShouldParser
{
    public function andThat(Expression $expression): self;

    public function should(Expression $expression, ?RuleException $ruleException): BecauseParser;
}
