<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\Match;

class MatchController
{
    public function viewAction(): void
    {
        new Match();
    }
}
