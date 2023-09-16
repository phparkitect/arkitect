<?php

declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Description;

class ViolationMessage
{
    /** @var string */
    private $rule;
    /** @var string|null */
    private $violation;

    private function __construct(string $rule, string $violation = '')
    {
        $this->rule = trim($rule);
        $this->violation = trim($violation);
    }

    public static function withDescription(Description $brokenRule, string $description): self
    {
        return new self($brokenRule->toString(), $description);
    }

    public static function selfExplanatory(Description $brokenRule): self
    {
        return new self($brokenRule->toString());
    }

    public function toString(): string
    {
        if ('' === $this->violation) {
            return $this->rule;
        }

        return "$this->violation\nfrom the rule\n$this->rule";
    }
}
