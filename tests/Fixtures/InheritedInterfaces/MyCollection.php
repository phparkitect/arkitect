<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\InheritedInterfaces;

class MyCollection extends \ArrayObject
{
    public function customMethod(): void
    {
    }
}
