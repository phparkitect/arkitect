<?php
declare(strict_types=1);

namespace ArkitectTests\unit\Costraints;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ConstraintsStore;
use Arkitect\Expression\HaveNameMatching;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ConstraintStoreTest extends TestCase
{
    public function test_it_should_not_add_to_violation_store_if_constraint_is_not_violated(): void
    {
        $expression = $this->prophesize(HaveNameMatching::class);

        $expressionStore = new ConstraintsStore();
        $expressionStore->add($expression->reveal());

        $classDescription = $this->prophesize(ClassDescription::class);
        $expression->isViolatedBy($classDescription)->willReturn(false);

        $violationStore = $this->prophesize(Violations::class);
        $violationStore->add(Argument::any())->shouldNotBeCalled();
        $expressionStore->checkAll($classDescription->reveal(), $violationStore->reveal());
    }

    public function test_it_should_add_to_violation_store_if_constraint_is_violated(): void
    {
        $expression = $this->prophesize(HaveNameMatching::class);

        $expressionStore = new ConstraintsStore();
        $expressionStore->add($expression->reveal());

        $classDescription = $this->prophesize(ClassDescription::class);
        $expression->isViolatedBy($classDescription)->willReturn(true);
        $expression->getViolationError($classDescription)->willReturn('bar');

        $violationStore = $this->prophesize(Violations::class);
        $violationStore->add(Argument::any())->shouldBeCalled();
        $expressionStore->checkAll($classDescription->reveal(), $violationStore->reveal());
    }
}
