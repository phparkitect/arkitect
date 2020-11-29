<?php
declare(strict_types=1);

namespace Arkitect\Expression;

class PositiveExpressionDescription implements ExpressionDescription
{
    private string $description;

    public function __construct(string $description)
    {
        $this->description = $description;
    }

    public function toString()
    {
        return $this->toPositive();
    }

    protected function toPositive()
    {
        // cerco nella stringa il pattern [positivo|negativo] e prendo il positivo
        return preg_replace('/\[(.+)\|(.+)]/i', '$1', $this->description);
    }

    public function getPattern()
    {
        return $this->description;
    }
}