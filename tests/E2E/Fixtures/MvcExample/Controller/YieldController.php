<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures\MvcExample\Controller;

use Arkitect\Tests\E2E\Fixtures\MvcExample\Model\Products;

class YieldController
{
    public function testingBug()
    {
        $class = Products::class;
        yield new $class();
    }
}
