<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;
use Arkitect\Expression\NegateDecorator;
use Arkitect\Rules\DSL\AndThatShouldParser;
use Arkitect\Rules\DSL\BecauseParser;
use Arkitect\Rules\DSL\ThatParser;

class NoClass implements ThatParser
{
    /** @var RuleBuilder */
    protected $ruleBuilder;

    public function __construct()
    {
        $this->ruleBuilder = (new RuleBuilder())->negateShoulds();
    }

    public function should(Expression $expression): BecauseParser
    {
        $this->ruleBuilder->addShould(new NegateDecorator($expression));

        return new Because($this->ruleBuilder);
    }

    public function that(Expression $expression): AndThatShouldParser
    {
        $this->ruleBuilder->addThat($expression);

        return new AndThatShould($this->ruleBuilder);
    }

    public function except(string ...$classesToBeExcluded): ThatParser
    {
        $this->ruleBuilder->classesToBeExcluded(...$classesToBeExcluded);

        return $this;
    }
}
