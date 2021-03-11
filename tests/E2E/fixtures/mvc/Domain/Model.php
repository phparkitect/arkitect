<?php

declare(strict_types=1);

namespace App\Domain;

use App\Services\CartService;
use App\Services\UserService;

class Model
{
    public function __construct()
    {
        $userService = new UserService();
        $cartService = new CartService();
    }
}
