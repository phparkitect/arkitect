<?php
declare(strict_types=1);

namespace Arkitect\DSL;

use Arkitect\Expression\Expression;
use Arkitect\Validation\Rule as ValidationRule;
use Arkitect\Validation\RuleBuilder;

class Rule implements ThatParser, BecauseParser, AndThatShouldParser
{
    /** @var RuleBuilder */
    private $ruleBuilder;

    public function __construct()
    {
        $this->ruleBuilder = new RuleBuilder();
    }

    public static function classes(): ThatParser
    {
        return new self();
    }

    public function that(Expression $expression): AndThatShouldParser
    {
        $this->ruleBuilder->withSelector($expression);

        return $this;
    }

    public function andThat(Expression $expression): AndThatShouldParser
    {
        $this->ruleBuilder->withSelector($expression);

        return $this;
    }

    public function should(Expression $expression): BecauseParser
    {
        $this->ruleBuilder->withAssertion($expression);

        return $this;
    }

    public function because(string $reason): ValidationRule
    {
        $this->ruleBuilder->withMessage($reason);

        return $this->ruleBuilder->build();
    }
}
