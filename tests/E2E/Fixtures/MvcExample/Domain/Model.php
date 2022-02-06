<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures\MvcExample\Domain;

use Arkitect\Tests\E2E\Fixtures\MvcExample\Services\UserService;

class Model
{
    public function __construct()
    {
        $userService = new UserService();
    }
}
