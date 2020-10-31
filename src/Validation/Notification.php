<?php
declare(strict_types=1);

namespace Arkitect\Validation;

class Notification
{
    /** @var string[] */
    private $errors;

    /** @var string[] */
    private $respectedRules;

    public function addError(string $error)
    {
        $this->errors[] = $error;
    }

    public function addRespectedRule(string $respectedRule)
    {
        $this->respectedRules[] = $respectedRule;
    }
}
