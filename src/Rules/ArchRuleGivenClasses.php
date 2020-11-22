<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ExpressionsStore;
use Arkitect\Specs\SpecsStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ArchRuleGivenClasses
{
    private \Arkitect\Specs\SpecsStore $specsStore;

    private \Arkitect\Expression\ExpressionsStore $expressionsStore;

    private \Arkitect\Rules\Violations $violationsStore;

    public function __construct()
    {
        $this->specsStore = new SpecsStore();
        $this->expressionsStore = new ExpressionsStore();
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
        $this->expressionsStore->add($expression);

        return $this;
    }

    public function check(ClassSet $set): void
    {
        $checkSub = new class($this->specsStore, $this->expressionsStore, $this->violationsStore) implements EventSubscriberInterface {
            private \Arkitect\Specs\SpecsStore $specsStore;

            private \Arkitect\Expression\ExpressionsStore $expressionsStore;

            private \Arkitect\Rules\Violations $violationsStore;

            public function __construct(SpecsStore $specStore, ExpressionsStore $expressionsStore, Violations $violationsStore)
            {
                $this->specsStore = $specStore;
                $this->expressionsStore = $expressionsStore;
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

                $this->expressionsStore->checkAll($classDescription, $this->violationsStore);
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
