<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NotResideInTheseNamespaces implements Expression
{
    /** @var array<string> */
    private array $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $descr = implode(', ', $this->namespaces);

        return new Description("should not reside in one of these namespaces: $descr", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $resideInNamespace = false;
        foreach ($this->namespaces as $namespace) {
            if ($this->residesIn($theClass, $namespace)) {
                $resideInNamespace = true;
            }
        }

        if ($resideInNamespace) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );
            $violations->add($violation);
        }
    }

    /**
     * Matching is recursive: a class in a child namespace resides in the given
     * namespace too. A pattern without wildcards already matches that way, but
     * one containing a wildcard is matched with fnmatch against the whole FQCN,
     * so it needs an explicit child-namespace pattern as well: without it
     * 'App\*\Infrastructure' would never match 'App\Foo\Infrastructure\Bar'.
     */
    private function residesIn(ClassDescription $theClass, string $namespace): bool
    {
        if ($theClass->namespaceMatches($namespace)) {
            return true;
        }

        return $theClass->namespaceMatches(rtrim($namespace, '\\').'\\*');
    }
}
