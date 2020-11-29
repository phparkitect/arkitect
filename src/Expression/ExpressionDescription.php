<?php
declare(strict_types=1);

namespace Arkitect\Expression;

class ExpressionDescription
{
    private string $description;

    private bool $positive;

    public function __construct(string $description)
    {
        $this->description = $description;
        $this->positive = true;
    }

    public function toggle(): self
    {
        $this->positive = !$this->positive;

        return $this;
    }

    public function toString()
    {
        return $this->positive ? $this->toPositive() : $this->toNegative();
    }

    protected function toPositive()
    {
        // cerco nella stringa il pattern [positivo|negativo] e prendo il positivo
        return preg_replace('/\[(.+)\|(.+)]/i', '$1', $this->description);
    }

    protected function toNegative()
    {
        // cerco nella stringa il pattern [positivo|negativo] e prendo il positivo
        return preg_replace('/\[(.+)\|(.+)]/i', '$2', $this->description);
    }

}