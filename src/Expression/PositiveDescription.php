<?php
declare(strict_types=1);

namespace Arkitect\Expression;

class PositiveDescription implements Description
{
    private string $description;

    public function __construct(string $description)
    {
        $this->description = $description;
    }

    public function toString(): string
    {
        return $this->toPositive();
    }

    public function getPattern(): string
    {
        return $this->description;
    }

    protected function toPositive(): string
    {
        // cerco nella stringa il pattern [positivo|negativo] e prendo il positivo
        return preg_replace('/\[(.+)\|(.+)]/i', '$1', $this->description);
    }
}
