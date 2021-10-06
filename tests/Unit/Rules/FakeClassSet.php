<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use Arkitect\ClassSet;

class FakeClassSet extends ClassSet
{
    public function __construct()
    {
    }

    public function getIterator()
    {
        return new \ArrayIterator([
            new FakeSplFileInfo('uno', '.', 'dir'),
            new FakeSplFileInfo('due', '.', 'dir'),
            new FakeSplFileInfo('tre', '.', 'dir'),
        ]);
    }
}
