<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Invalid\Attr as InvalidAttr;
use App\Service\Valid\Attr as ValidAttr;

#[ValidAttr, InvalidAttr]
class Foo
{
}
