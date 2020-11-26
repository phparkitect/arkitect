<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\DSL\BecauseParser;

class Because implements BecauseParser
{
    private RuleBuilder $expressionBuilder;

    public function __construct(RuleBuilder $expressionBuilder)
    {
        $this->expressionBuilder = $expressionBuilder;
    }

    public function because(string $reason): ArchRule
    {
        $this->expressionBuilder->setBecause($reason);

        return $this->expressionBuilder->build();
    }
}
