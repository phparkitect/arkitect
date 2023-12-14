<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\ComponentB;

use Arkitect\Tests\Fixtures\ComponentA\ClassAWithoutDependencies;
use Arkitect\Tests\Fixtures\ComponentC\ComponentCA\ClassCAWithoutDependencies;

final class ClassBDependingOnAD
{
    private $a;

    private $d;

    public function __construct()
    {
        $this->a = new ClassAWithoutDependencies();
        $this->d = new ClassCAWithoutDependencies();
    }
}
