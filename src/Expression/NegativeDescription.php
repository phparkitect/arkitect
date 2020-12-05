<?php
declare(strict_types=1);

namespace Arkitect\Expression;

class NegativeDescription implements Description
{
    private Description $description;

    public function __construct(Description $description)
    {
        $this->description = $description;
    }

    public function toString(): string
    {
        return $this->toNegative();
    }

    public function getPattern(): string
    {
        $this->description->getPattern();
    }

    protected function toNegative(): string
    {
        // cerco nella stringa il pattern [positivo|negativo] e prendo il negativo
        return preg_replace('/\[(.+)\|(.+)]/i', '$2', $this->description->getPattern());
    }
}