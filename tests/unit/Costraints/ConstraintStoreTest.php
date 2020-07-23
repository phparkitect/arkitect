<?php
declare(strict_types=1);

namespace ArkitectTests\unit\Costraints;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Constraints\ConstraintsStore;
use Arkitect\Constraints\HaveNameMatching;
use Arkitect\Rules\ViolationsStore;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ConstraintStoreTest extends TestCase
{
    public function test_it_should_not_add_to_violation_store_if_constraint_is_not_violated(): void
    {
        $constraint = $this->prophesize(HaveNameMatching::class);

        $constraintStore = new ConstraintsStore();
        $constraintStore->add($constraint->reveal());

        $classDescription = $this->prophesize(ClassDescription::class);
        $constraint->isViolatedBy($classDescription)->willReturn(false);

        $violationStore = $this->prophesize(ViolationsStore::class);
        $violationStore->add(Argument::any())->shouldNotBeCalled();
        $constraintStore->checkAll($classDescription->reveal(), $violationStore->reveal());
    }

    public function test_it_should_add_to_violation_store_if_constraint_is_violated(): void
    {
        $constraint = $this->prophesize(HaveNameMatching::class);

        $constraintStore = new ConstraintsStore();
        $constraintStore->add($constraint->reveal());

        $classDescription = $this->prophesize(ClassDescription::class);
        $constraint->isViolatedBy($classDescription)->willReturn(true);
        $constraint->getViolationError($classDescription)->willReturn('bar');

        $violationStore = $this->prophesize(ViolationsStore::class);
        $violationStore->add(Argument::any())->shouldBeCalled();
        $constraintStore->checkAll($classDescription->reveal(), $violationStore->reveal());
    }
}
