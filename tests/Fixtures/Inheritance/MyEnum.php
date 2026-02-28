<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\Inheritance;

enum MyEnum implements \IteratorAggregate
{
    case FOO;

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([]);
    }
}
