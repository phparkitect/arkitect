<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;
use Arkitect\Rules\DSL\AndThatShouldParser;
use Arkitect\Rules\DSL\BecauseParser;

class AndThatShould implements AndThatShouldParser
{
    private RuleBuilder $expressionBuilder;

    public function __construct(RuleBuilder $expressionBuilder)
    {
        $this->expressionBuilder = $expressionBuilder;
    }

    public function andThat(Expression $expression): AndThatShouldParser
    {
        $this->expressionBuilder->addThat($expression);

        return $this;
    }

    public function should(Expression $expression): BecauseParser
    {
        $this->expressionBuilder->addShould($expression);

        return new Because($this->expressionBuilder);
    }
}
