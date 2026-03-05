<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\Hierarchy;

class ParentClass implements BaseInterface
{
    use BaseTrait;

    public function baseMethod(): void
    {
    }
}
