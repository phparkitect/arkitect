<?php
declare(strict_types=1);

namespace Arkitect\Exceptions;

class ViolationNotFoundException extends \Exception
{
    public function __construct(int $index)
    {
        parent::__construct(sprintf('Violation not found with index %d', $index));
    }
}
