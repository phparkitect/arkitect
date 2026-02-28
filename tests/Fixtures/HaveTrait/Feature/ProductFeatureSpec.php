<?php

declare(strict_types=1);

namespace Arkitect\Tests\Fixtures\HaveTrait\Feature;

use Arkitect\Tests\Fixtures\HaveTrait\DatabaseTransactions;
use Arkitect\Tests\Fixtures\HaveTrait\RefreshDatabase;

class ProductFeatureSpec
{
    use DatabaseTransactions;
    use RefreshDatabase;
}
