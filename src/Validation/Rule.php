<?php
declare(strict_types=1);

namespace Arkitect\Validation;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class Rule
{
    /** @var Expression[] */
    private $selectors;

    /** @var Expression */
    private $assertion;

    /** @var ?string */
    private $reason;

    public function __construct(array $selectors, Expression $assertion, ?string $reason)
    {
        $this->selectors = $selectors;
        $this->assertion = $assertion;
        $this->reason = $reason;
    }

    public function check(Notification $notification, ClassDescription $class): void
    {
        $ruleDescription = sprintf('%s %s', $class->getFQCN(), $this->assertion->toString());
        if (!$this->assertion->evaluate($class)) {
            $notification->addError($ruleDescription);
        } else {
            $notification->addRespectedRule($ruleDescription);
        }
    }

    public function appliesTo(ClassDescription $item): bool
    {
        /** @var Expression $selector */
        foreach ($this->selectors as $selector) {
            if (!$selector->evaluate($item)) {
                return false;
            }
        }

        return true;
    }
}
