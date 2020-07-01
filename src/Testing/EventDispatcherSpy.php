<?php

namespace Arkitect\Testing;

use Psr\EventDispatcher\EventDispatcherInterface;

class EventDispatcherSpy implements EventDispatcherInterface
{
    private $events = [];

    /**
     * @inheritDoc
     */
    public function dispatch(object $event)
    {
        $this->events[] = $event;
    }

    public function getDispatchedEvents(): array
    {
        return $this->events;
    }
}