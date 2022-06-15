<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Products;

#[\AsController]
class YieldController
{
    public function testingBug()
    {
        $class = Products::class;
        yield new $class();
    }
}
