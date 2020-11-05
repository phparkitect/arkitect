<?php
declare(strict_types=1);

namespace Arkitect\Validation;

class Notification
{
    /** @var string[] */
    private $errors = [];

    /** @var string[] */
    private $respectedRules = [];

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

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): int
    {
        return \count($this->errors);
    }

    public function respectedRules(): array
    {
        return $this->respectedRules;
    }
}
