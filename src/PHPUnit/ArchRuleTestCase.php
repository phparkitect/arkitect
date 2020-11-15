<?php
declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Rules\ArchRuleGivenClasses;
use PHPUnit\Framework\Constraint\Constraint;

class ArchRuleTestCase extends \PHPUnit\Framework\TestCase
{
    public static function assertArchRule(ArchRuleGivenClasses $rule, ClassSet $set): void
    {
        $expression = new class($rule) extends Constraint {
            private $rule;

            public function __construct(ArchRuleGivenClasses $rule)
            {
                $this->rule = $rule;
            }

            protected function matches($set): bool
            {
                $this->rule->check($set);

                $violations = $this->rule->getViolations();

                return 0 === \count($violations);
            }

            public function toString(): string
            {
                return 'satifies all constraints';
            }

            protected function failureDescription($other): string
            {
                return $this->rule->getViolations()->toString();
            }
        };

        static::assertThat($set, $expression, '');
    }
}
