<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ConstraintsStore;
use Arkitect\Specs\SpecsStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ArchRuleGivenClasses
{
    private $specsStore;

    private $expressionsStore;

    private $violationsStore;

    public function __construct()
    {
        $this->specsStore = new SpecsStore();
        $this->constraintsStore = new ConstraintsStore();
        $this->violationsStore = new Violations();
    }

    public function that(Expression $expression): self
    {
        $this->specsStore->add($expression);

        return $this;
    }

    public function andThat(Expression $expression): self
    {
        return $this->that($expression);
    }

    public function should(Expression $expression): self
    {
        $this->constraintsStore->add($expression);

        return $this;
    }

    public function check(ClassSet $set): void
    {
        $checkSub = new class($this->specsStore, $this->constraintsStore, $this->violationsStore) implements EventSubscriberInterface {
            private $specsStore;

            private $expressionsStore;

            private $violationsStore;

            public function __construct(SpecsStore $specStore, ConstraintsStore $expressionsStore, Violations $violationsStore)
            {
                $this->specsStore = $specStore;
                $this->constraintsStore = $expressionsStore;
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
