<?php
declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Rules\RuleChecker;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Constraint\Constraint;

class ArchRuleCheckerConstraintAdapter extends Constraint
{
    private ClassSet $classSet;

    private Violations $violations;

    public function __construct(ClassSet $classSet)
    {
        $this->classSet = $classSet;
    }

    public function toString(): string
    {
        return 'satisfies all architectural constraints';
    }

    protected function matches(/** ArchRule */ $rule): bool
    {
        $ruleChecker = RuleChecker::build($this->classSet, $rule);

        $this->violations = $ruleChecker->run();

        return 0 === $this->violations->count();
    }

    protected function failureDescription($other): string
    {
        return "\n".$this->violations->toString();
    }
}
