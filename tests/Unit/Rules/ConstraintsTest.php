<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Constraints;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ConstraintsTest extends TestCase
{
    public function test_it_should_not_add_to_violation_if_constraint_is_not_violated(): void
    {
        $trueExpression = new class() implements Expression {
            public function describe(ClassDescription $theClass): Description
            {
                return new PositiveDescription('');
            }

            public function evaluate(ClassDescription $theClass, Violations $violations): void
            {
            }
        };

        $expressionStore = new Constraints();
        $expressionStore->add($trueExpression);
        $violations = new Violations();

        $expressionStore->checkAll(
            ClassDescriptionBuilder::create('Banana')->get(),
            $violations
        );

        $this->assertCount(0, $violations);
    }

    public function test_it_should_add_to_violation_store_if_constraint_is_violated(): void
    {
        $falseExpression = new class() implements Expression {
            public function describe(ClassDescription $theClass): Description
            {
                return new PositiveDescription('bar');
            }

            public function evaluate(ClassDescription $theClass, Violations $violations): void
            {
                $violation = Violation::create(
                    $theClass->getFQCN(),
                    $this->describe($theClass)->toString()
                );

                $violations->add($violation);
            }
        };

        $expressionStore = new Constraints();
        $expressionStore->add($falseExpression);
        $violations = new Violations();

        $expressionStore->checkAll(
            ClassDescriptionBuilder::create('Banana')->get(),
            $violations
        );

        $this->assertCount(1, $violations);
    }
}
