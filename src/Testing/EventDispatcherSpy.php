<?php
declare(strict_types=1);

namespace Arkitect\Testing;

use Psr\EventDispatcher\EventDispatcherInterface;

class EventDispatcherSpy implements EventDispatcherInterface
{
    /** @var array */
    private $events = [];

    public function dispatch(object $event)
    {
        $this->events[] = $event;

        return $event;
    }

    public function getDispatchedEvents(): array
    {
        return $this->events;
    }
}
