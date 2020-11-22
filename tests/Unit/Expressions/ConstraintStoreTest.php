<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ExpressionsStore;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ConstraintStoreTest extends TestCase
{
    public function test_it_should_not_add_to_violation_store_if_constraint_is_not_violated(): void
    {
        $expression = $this->prophesize(HaveNameMatching::class);

        $expressionStore = new ExpressionsStore();
        $expressionStore->add($expression->reveal());

        $classDescription = $this->prophesize(ClassDescription::class);
        $expression->evaluate($classDescription)->willReturn(true);

        $violationStore = $this->prophesize(Violations::class);
        $violationStore->add(Argument::any())->shouldNotBeCalled();

        $expressionStore->checkAll($classDescription->reveal(), $violationStore->reveal());
    }

    public function test_it_should_add_to_violation_store_if_constraint_is_violated(): void
    {
        $expression = $this->prophesize(HaveNameMatching::class);

        $expressionStore = new ExpressionsStore();
        $expressionStore->add($expression->reveal());

        $classDescription = $this->prophesize(ClassDescription::class);
        $expression->evaluate($classDescription)->willReturn(false);
        $expression->describe($classDescription)->willReturn('bar');

        $violationStore = $this->prophesize(Violations::class);
        $violationStore->add(Argument::any())->shouldBeCalled();
        $expressionStore->checkAll($classDescription->reveal(), $violationStore->reveal());
    }
}
