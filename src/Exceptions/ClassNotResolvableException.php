<?php
declare(strict_types=1);

namespace Arkitect\Exceptions;

class ClassNotResolvableException extends \RuntimeException
{
    public static function parentNotFound(string $className, string $parentName, \Throwable $previous): self
    {
        return new self(
            \sprintf('Cannot resolve parent class "%s" for class "%s". Ensure the source directory containing "%s" is included in the analysis.', $parentName, $className, $parentName),
            0,
            $previous
        );
    }

    public static function interfaceNotFound(string $className, \Throwable $previous): self
    {
        return new self(
            \sprintf('Cannot resolve interfaces for class "%s". Ensure all interface source directories are included in the analysis.', $className),
            0,
            $previous
        );
    }

    public static function traitNotFound(string $className, \Throwable $previous): self
    {
        return new self(
            \sprintf('Cannot resolve traits for class "%s". Ensure all trait source directories are included in the analysis.', $className),
            0,
            $previous
        );
    }
}
