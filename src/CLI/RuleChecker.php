<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use Arkitect\Validation\Engine;
use Arkitect\Validation\Notification;
use Arkitect\Validation\Rule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RuleChecker implements EventSubscriberInterface
{
    /**
     * @var ClassSet
     */
    private $classSet;

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var Notification[]
     */
    private $notifications = [];

    public function __construct()
    {
    }

    public function checkThatClassesIn(ClassSet $classSet): self
    {
        $this->classSet = $classSet;

        return $this;
    }

    public function meetTheFollowingRules(Rule ...$rules): self
    {
        $this->engine = new Engine();
        $this->engine->addRules($rules);

        return $this;
    }

    /**
     * @return Notification[]
     */
    public function run(): array
    {
        $this->classSet->addSubscriber($this);

        $this->classSet->run();

        return $this->notifications;
    }

    public static function getSubscribedEvents()
    {
        return [
            ClassAnalyzed::class => 'onClassAnalyzed',
        ];
    }

    public function onClassAnalyzed(ClassAnalyzed $classAnalyzed): void
    {
        $item = $classAnalyzed->getClassDescription();

        $this->notifications[] = $this->engine->run($item);
    }
}
