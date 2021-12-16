<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;
use Arkitect\Rules\DSL\AndThatShouldParser;
use Arkitect\Rules\DSL\ThatParser;

class AllClasses implements ThatParser
{
    /** @var RuleBuilder */
    protected $ruleBuilder;
    /** @var array */
    private $classesToBeExcluded;

    public function __construct()
    {
        $this->classesToBeExcluded = [];
        $this->ruleBuilder = new RuleBuilder();
    }

    public function that(Expression $expression): AndThatShouldParser
    {
        $this->ruleBuilder->addThat($expression);

        return new AndThatShould($this->ruleBuilder);
    }

    public function exclude(string ...$classesToBeExcluded): ThatParser
    {
        $this->ruleBuilder->classesToBeExcluded(...$classesToBeExcluded);

        return $this;
    }
}
