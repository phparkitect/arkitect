<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\Fruit;

use Arkitect\Tests\Fixtures\Animal\Cat;

final class AnimalFruit extends Banana
{
    /**
     * @var Cat
     */
    private $cat;

    public function __construct(Cat $cat)
    {
        $this->cat = $cat;
    }
}
