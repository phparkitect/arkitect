<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Valid\Attr as ValidAttr;
use App\Service\Invalid\Attr as InvalidAttr;

#[ValidAttr, InvalidAttr]
class Foo
{

}
