<?php
declare(strict_types=1);

namespace Arkitect\Expression;

class NegativeExpressionDescription implements ExpressionDescription
{
    private ExpressionDescription $description;

    public function __construct(ExpressionDescription $description)
    {
        $this->description = $description;
    }

    public function toString()
    {
        return $this->toNegative();
    }

    protected function toNegative()
    {
        // cerco nella stringa il pattern [positivo|negativo] e prendo il positivo
        return preg_replace('/\[(.+)\|(.+)]/i', '$2', $this->description->getPattern());
    }

    public function getPattern()
    {
        $this->description->getPattern();
    }
}