<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\DSL\BecauseParser;

class Because implements BecauseParser
{
    /** @var RuleBuilder */
    private $ruleBuilder;

    public function __construct(RuleBuilder $expressionBuilder)
    {
        $this->ruleBuilder = $expressionBuilder;
    }

    public function because(string $reason): ArchRule
    {
        $this->ruleBuilder->setBecause($reason);

        return $this->ruleBuilder->build();
    }
}
