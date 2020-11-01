<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class ImplementInterface implements Expression
{
    /**
     * @var string
     */
    private $FQCN;

    public function __construct(string $FQCN)
    {
        $this->FQCN = $FQCN;
    }

    public function __invoke(ClassDescription $class): bool
    {
        return $class->implements($this->FQCN);
    }
}
