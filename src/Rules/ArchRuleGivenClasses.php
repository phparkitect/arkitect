<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use Arkitect\Constraints\Constraint;
use Arkitect\Constraints\ConstraintsStore;
use Arkitect\Specs\BaseSpec;
use Arkitect\Specs\SpecsStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ArchRuleGivenClasses
{
    private $specsStore;

    private $constraintsStore;

    private $violationsStore;

    public function __construct()
    {
        $this->specsStore = new SpecsStore();
        $this->constraintsStore = new ConstraintsStore();
        $this->violationsStore = new Violations();
    }

    public function that(Constraint $constraint): self
    {
        $this->specsStore->add($constraint);

        return $this;
    }

    public function andThat(Constraint $constraint): self
    {
        return $this->that($constraint);
    }

    public function should(Constraint $constraint): self
    {
        $this->constraintsStore->add($constraint);

        return $this;
    }

    public function check(ClassSet $set): void
    {
        $checkSub = new class($this->specsStore, $this->constraintsStore, $this->violationsStore) implements EventSubscriberInterface {
            private $specsStore;

            private $constraintsStore;

            private $violationsStore;

            public function __construct(SpecsStore $specStore, ConstraintsStore $constraintsStore, Violations $violationsStore)
            {
                $this->specsStore = $specStore;
                $this->constraintsStore = $constraintsStore;
                $this->violationsStore = $violationsStore;
            }

            public static function getSubscribedEvents()
            {
                return [
                    ClassAnalyzed::class => 'onClassAnalyzed',
                ];
            }

            public function onClassAnalyzed(ClassAnalyzed $classAnalyzed): void
            {
                $classDescription = $classAnalyzed->getClassDescription();

                if (!$this->specsStore->allSpecsAreMatchedBy($classDescription)) {
                    return;
                }

                $this->constraintsStore->checkAll($classDescription, $this->violationsStore);
            }
        };

        $set->addSubscriber($checkSub);
        $set->run();
    }

    public function getViolations(): Violations
    {
        return $this->violationsStore;
    }
}
