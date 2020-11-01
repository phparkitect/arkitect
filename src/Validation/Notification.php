<?php
declare(strict_types=1);

namespace Arkitect\Validation;

class Notification
{
    /** @var string[] */
    private $errors = [];

    /** @var string[] */
    private $respectedRules = [];

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->errors);
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function addRespectedRule(string $respectedRule): void
    {
        $this->respectedRules[] = $respectedRule;
    }

    public function getErrorCount(): int
    {
        return \count($this->errors);
    }
}
