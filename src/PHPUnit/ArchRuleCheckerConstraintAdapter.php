<?php
declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\RuleChecker;
use PHPUnit\Framework\Constraint\Constraint;

class ArchRuleCheckerConstraintAdapter extends Constraint
{
    private ArchRule $rule;
    private RuleChecker $ruleChecker;

    public function __construct(ArchRule $rule)
    {
        $this->rule = $rule;
        $this->ruleChecker = new RuleChecker();
    }

    public function toString(): string
    {
        return 'satisfies all architectural constraints';
    }

    /**
     * @param ClassSet $set
     */
    protected function matches($set): bool
    {
        $this->ruleChecker->check($this->rule, $set);

        return !$this->ruleChecker->hasViolations();
    }

    /**
     * @param ClassSet $set
     * @param mixed    $other
     */
    protected function failureDescription($other): string
    {
        return $this->ruleChecker->getViolations()->toString();
    }
}
