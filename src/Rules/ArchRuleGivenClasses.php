<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use Arkitect\Constraints\ArchRuleConstraint;
use Arkitect\Constraints\ConstraintsStore;
use Arkitect\Specs\ArchRuleSpec;
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
        $this->violationsStore = new ViolationsStore();
    }

    public function that(): ArchRuleSpec
    {
        return new ArchRuleSpec($this, $this->specsStore, $this->constraintsStore);
    }

    public function should(): ArchRuleConstraint
    {
        return new ArchRuleConstraint($this, $this->constraintsStore);
    }

    public function check(ClassSet $set): void
    {
        $checkSub = new class($this->specsStore, $this->constraintsStore, $this->violationsStore) implements EventSubscriberInterface {
            private $specsStore;

            private $constraintsStore;

            private $violationsStore;

            public function __construct(SpecsStore $specStore, ConstraintsStore $constraintsStore, ViolationsStore $violationsStore)
            {
                $this->specsStore = $specStore;
                $this->constraintsStore = $constraintsStore;
                $this->violationsStore = $violationsStore;
            }

            public static function getSubscribedEvents()
            {
                return [
                    ClassAnalyzed::class => 'onClassAnalyzed'
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

    public function getViolations(): ViolationsStore
    {
        return $this->violationsStore;
    }

    public function get(): ArchRuleGivenClasses
    {
        return $this;
    }
}
