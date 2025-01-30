<?php

declare(strict_types=1);

namespace App\Application;

final class Foo
{
    public function doSomething(\App\Ui\Baz $baz): void
    {
    }

    public function doSomethingElse(\App\Ui\Bar $bar): void
    {
    }

    public function doSomethingElseEvenMore(\App\Ui\Bar $bar): void
    {
    }
}
