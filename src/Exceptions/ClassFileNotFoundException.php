<?php

declare(strict_types=1);

namespace Arkitect\Exceptions;

final class ClassFileNotFoundException extends \Exception
{
    public function __construct(string $fqcn)
    {
        parent::__construct("Could not find file for class '$fqcn'");
    }
}
