<?php
declare(strict_types=1);

namespace Arkitect\Exceptions;

class PhpVersionNotValidException extends \Exception
{
    public function __construct(string $phpVersion)
    {
        parent::__construct(\sprintf('PHP version not valid for PHPArkitect parser %s', $phpVersion));
    }
}
