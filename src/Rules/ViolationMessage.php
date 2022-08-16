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

    private function __construct(string $rule, ?string $violation)
    {
        $this->rule = $rule;
        $this->violation = $violation;
    }

    public static function withDescription(Description $brokenRule, string $description): self
    {
        return new self($brokenRule->toString(), $description);
    }

    public static function selfExplanatory(Description $brokenRule): self
    {
        return new self($brokenRule->toString(), null);
    }

    public function toString(): string
    {
        if (null === $this->violation) {
            return $this->rule;
        }

        return "$this->violation, but $this->rule";
    }
}
