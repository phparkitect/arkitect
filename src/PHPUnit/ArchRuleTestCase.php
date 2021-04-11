<?php
declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Rules\DSL\ArchRule;

class ArchRuleTestCase extends \PHPUnit\Framework\TestCase
{
    public static function assertArchRule(ArchRule $rule, ClassSet $set): void
    {
        $constraint = new ArchRuleCheckerConstraintAdapter($set);

        static::assertThat($rule, $constraint);
    }
}
