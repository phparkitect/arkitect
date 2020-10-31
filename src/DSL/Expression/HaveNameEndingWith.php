<?php
declare(strict_types=1);

namespace Arkitect\DSL\Expression;

use Arkitect\DSL\Expression;

class HaveNameEndingWith implements Expression
{
    /**
     * @var string
     */
    private $nameEnding;

    public function __construct(string $nameEnding)
    {
        $this->nameEnding = $nameEnding;
    }
}
