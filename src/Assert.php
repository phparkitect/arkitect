<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Rules\ArchRuleGivenClasses;

class Assert
{
    /**
     * @var ClassSet
     */
    private $set;

    /**
     * @var ArchRuleGivenClasses
     */
    private $rule;

    public function __construct(ClassSet $classSet, ArchRuleGivenClasses $rule)
    {
        $this->set = $classSet;
        $this->rule = $rule;
    }

    public function run(): void
    {
        $this->rule->check($this->set);

        $violations = $this->rule->getViolations();

        if (count($violations) > 0) {
            throw new ArchViolationsException($violations);
        }
    }
}
