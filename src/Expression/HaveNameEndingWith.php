<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

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

    public function __invoke(ClassDescription $class): bool
    {
        return true; // TODO
    }
}
