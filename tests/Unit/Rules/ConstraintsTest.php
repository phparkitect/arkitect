<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Constraints;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ConstraintsTest extends TestCase
{
    public function testItShouldNotAddToViolationIfConstraintIsNotViolated(): void
    {
        $trueExpression = new class() implements Expression {
            public function describe(ClassDescription $theClass): Description
            {
                return new PositiveDescription('');
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
            ClassDescriptionBuilder::create('Banana')->get(),
            $violations
        );

        $this->assertCount(0, $violations);
    }

    public function testItShouldAddToViolationStoreIfConstraintIsViolated(): void
    {
        $falseExpression = new class() implements Expression {
            public function describe(ClassDescription $theClass): Description
            {
                return new PositiveDescription('bar');
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
            ClassDescriptionBuilder::create('Banana')->get(),
            $violations
        );

        $this->assertCount(1, $violations);
    }
}
