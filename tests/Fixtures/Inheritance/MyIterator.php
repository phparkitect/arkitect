<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\Inheritance;

class MyIterator implements \IteratorAggregate
{
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([]);
    }
}
