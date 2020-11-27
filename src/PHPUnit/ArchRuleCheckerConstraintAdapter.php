<?php
declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Rules\RuleChecker;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Constraint\Constraint;

class ArchRuleCheckerConstraintAdapter extends Constraint
{
    private RuleChecker $ruleChecker;

    public function __construct(ClassSet $classSet, Violations $violations)
    {
        $this->ruleChecker = new RuleChecker($classSet, $violations);
    }

    public function toString(): string
    {
        return 'satisfies all architectural constraints';
    }

    protected function matches(/** ArchRule */ $rule): bool
    {
        $this->ruleChecker->check($rule);

        return !$this->ruleChecker->hasViolations();
    }

    protected function failureDescription($other): string
    {
        return $this->ruleChecker->getViolations()->toString();
    }
}
