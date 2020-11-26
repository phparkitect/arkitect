<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RuleChecker implements EventSubscriberInterface
{
    private Violations $violations;

    private \Arkitect\Rules\DSL\ArchRule $rule;

    public function __construct()
    {
        $this->violations = new Violations();
    }

    public function check(DSL\ArchRule $rule, ClassSet $set): void
    {
        $this->rule = $rule;

        $set->addSubscriber($this);
        $set->run();
    }

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function hasViolations(): bool
    {
        return 0 !== $this->violations->count();
    }

    public function onClassAnalyzed(ClassAnalyzed $classAnalyzed): void
    {
        $classDescription = $classAnalyzed->getClassDescription();

        $this->rule->check($classDescription, $this->violations);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClassAnalyzed::class => 'onClassAnalyzed',
        ];
    }
}
