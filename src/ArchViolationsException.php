<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Rules\Violations;

class ArchViolationsException extends \Exception
{
    private \Arkitect\Rules\Violations $violations;

    public function __construct(Violations $violations)
    {
        parent::__construct(sprintf('%d architectural violations detected', $violations->count()));

        $this->violations = $violations;
    }

    public function violations(): Violations
    {
        return $this->violations;
    }
}
