<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\PHPUnit\ArchRuleCheckerConstraintAdapter;
use Arkitect\Rules\DSL\ArchRule;
use PHPUnit\Framework\TestCase;

class ArchRuleTestCase extends TestCase
{
    public static function assertArchRule(ArchRule $rule, ClassSet $set): void
    {
        $constraint = new ArchRuleCheckerConstraintAdapter($set);

        static::assertThat($rule, $constraint);
    }
}
