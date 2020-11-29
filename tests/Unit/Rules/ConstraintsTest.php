<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ExpressionDescription;
use Arkitect\Rules\Constraints;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ConstraintsTest extends TestCase
{
    public function test_it_should_not_add_to_violation_if_constraint_is_not_violated(): void
    {
        $trueExpression = new class() implements Expression {
            public function describe(ClassDescription $theClass): ExpressionDescription
            {
                return new ExpressionDescription('');
            }

            public function evaluate(ClassDescription $theClass): bool
            {
                return true;
            }
        };

        $expressionStore = new Constraints();
        $expressionStore->add($trueExpression);
        $violations = new Violations();

        $expressionStore->checkAll(
            $this->prophesize(ClassDescription::class)->reveal(),
            $violations
        );

        $this->assertCount(0, $violations);
    }

    public function test_it_should_add_to_violation_store_if_constraint_is_violated(): void
    {
        $falseExpression = new class() implements Expression {
            public function describe(ClassDescription $theClass): ExpressionDescription
            {
                return new ExpressionDescription('bar');
            }

            public function evaluate(ClassDescription $theClass): bool
            {
                return false;
            }
        };

        $expressionStore = new Constraints();
        $expressionStore->add($falseExpression);
        $violations = new Violations();

        $expressionStore->checkAll(
            $this->prophesize(ClassDescription::class)->reveal(),
            $violations
        );

        $this->assertCount(1, $violations);
    }
}
