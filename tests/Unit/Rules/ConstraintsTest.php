<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Constraints;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ConstraintsTest extends TestCase
{
    public function test_it_should_not_add_to_violation_if_constraint_is_not_violated(): void
    {
        $trueExpression = new class implements Expression {
            public function describe(ClassDescription $theClass, string $because): Description
            {
                return new Description('', '');
            }

            public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
            {
            }
        };

        $expressionStore = new Constraints();
        $expressionStore->add($trueExpression);
        $violations = new Violations();
        $because = 'we want to add this rule for our software';

        $cb = new ClassDescriptionBuilder();
        $cb->setClassName('Banana');

        $expressionStore->checkAll(
            $cb->build(),
            $violations,
            $because
        );

        $this->assertCount(0, $violations);
    }

    public function test_it_should_add_to_violation_store_if_constraint_is_violated(): void
    {
        $falseExpression = new class implements Expression {
            public function describe(ClassDescription $theClass, string $because): Description
            {
                return new Description('bar', 'we want to add this rule');
            }

            public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
            {
                $violation = Violation::create(
                    $theClass->getFQCN(),
                    ViolationMessage::selfExplanatory($this->describe($theClass, $because))
                );

                $violations->add($violation);
            }
        };

        $expressionStore = new Constraints();
        $expressionStore->add($falseExpression);
        $violations = new Violations();
        $because = 'we want to add this rule for our software';

        $cb = new ClassDescriptionBuilder();
        $cb->setClassName('Banana');

        $expressionStore->checkAll(
            $cb->build(),
            $violations,
            $because
        );

        $this->assertCount(1, $violations);
    }
}
