<?php
declare(strict_types=1);

namespace Arkitect\Validation;

use Arkitect\DSL\Expression;
use Arkitect\Validation\Rule;

class RuleBuilder
{
    /** @var Expression[] */
    private $selectors = [];

    /** @var Expression */
    private $assertion;

    /** @var string */
    private $message;

    public function withSelector(Expression $selector): self
    {
        $this->selectors[] = $selector;

        return $this;
    }

    public function withAssertion(Expression $assertion): self
    {
        $this->assertion = $assertion;

        return $this;
    }

    public function withMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function build(): Rule
    {
        return new Rule($this->selectors, $this->assertion, $this->message);
    }
}
