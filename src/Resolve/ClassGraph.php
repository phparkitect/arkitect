<?php

declare(strict_types=1);

namespace Arkitect\Resolve;

use Arkitect\Parser\ParsedClass;

final class ClassGraph
{
    /** @var array<string, ParsedClass> */
    private array $byFqcn;

    public function __construct(ParsedClass ...$classes)
    {
        $this->byFqcn = [];
        foreach ($classes as $class) {
            $this->byFqcn[$class->fqcn] = $class;
        }
    }

    public function isA(string $fqcn, string $target): Membership
    {
        if ($fqcn === $target) {
            return Membership::Yes;
        }

        $class = $this->byFqcn[$fqcn] ?? null;

        if (null === $class) {
            return Membership::Unknown;
        }

        $anyUnknown = false;

        foreach ([...$class->extends, ...$class->implements] as $parent) {
            $result = $this->isA($parent->name, $target);

            if (Membership::Yes === $result) {
                return Membership::Yes;
            }

            if (Membership::Unknown === $result) {
                $anyUnknown = true;
            }
        }

        return $anyUnknown ? Membership::Unknown : Membership::No;
    }
}
