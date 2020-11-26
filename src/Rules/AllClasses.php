<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;
use Arkitect\Rules\DSL\AndThatShouldParser;
use Arkitect\Rules\DSL\ThatParser;

class AllClasses implements ThatParser
{
    protected RuleBuilder $expressionBuilder;

    public function __construct()
    {
        $this->expressionBuilder = new RuleBuilder();
    }

    public function that(Expression $expression): AndThatShouldParser
    {
        $this->expressionBuilder->addThat($expression);

        return new AndThatShould($this->expressionBuilder);
    }
}
