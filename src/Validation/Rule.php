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

    /** @var string */
    private $message;

    public function __construct(array $selectors, Expression $assertion, string $message)
    {
        $this->selectors = $selectors;
        $this->assertion = $assertion;
        $this->message = $message;
    }

    public function check(Notification $notification, ClassDescription $class): void
    {
        if (!$this->assertion->evaluate($class)) {
            $notification->addError(sprintf('Failed asserting that %s %s', $class->getFQCN(), $this->assertion->toString()));
        } else {
            $notification->addRespectedRule(sprintf('%s %s', $class->getFQCN(), $this->assertion->toString()));
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
