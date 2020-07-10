<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Rules\ViolationsStore;

class ArchViolationsException extends \Exception
{
    /**
     * @var ViolationsStore
     */
    private $violations;

    public function __construct(ViolationsStore $violations)
    {
        parent::__construct('Architectural violations detected');

        $this->violations = $violations;
    }

    public function violations(): ViolationsStore
    {
        return $this->violations;
    }
}
