<?php
declare(strict_types=1);

namespace Arkitect\Exceptions;

class IndexNotFoundException extends \Exception
{
    public function __construct(int $index)
    {
        parent::__construct(sprintf('Index not found %d', $index));
    }
}
