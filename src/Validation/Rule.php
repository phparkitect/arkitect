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

    public function check(Notification $notification, ClassDescription $item): void
    {
        if (!($this->assertion)($item)) {
            $notification->addError(sprintf("Validation of '%s' failed because '%s'.", $item->getFQCN(), $this->message));
        } else {
            $notification->addRespectedRule(sprintf("'%s' is correct because '%s'.", $item->getFQCN(), $this->message));
        }
    }

    public function appliesTo(ClassDescription $item): bool
    {
        /** @var Expression $selector */
        foreach ($this->selectors as $selector) {
            if (!$selector($item)) {
                return false;
            }
        }

        return true;
    }
}
