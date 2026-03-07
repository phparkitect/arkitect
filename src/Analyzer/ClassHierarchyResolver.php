<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;

class ClassHierarchyResolver
{
    private Reflector $reflector;

    /**
     * @param list<string> $directories
     */
    public function __construct(array $directories)
    {
        $betterReflection = new BetterReflection();
        $astLocator = $betterReflection->astLocator();

        $this->reflector = new DefaultReflector(new AggregateSourceLocator([
            new DirectoriesSourceLocator($directories, $astLocator),
            new PhpInternalSourceLocator($astLocator, $betterReflection->sourceStubber()),
        ]));
    }

    /**
     * Get all parent class names in the full inheritance hierarchy.
     *
     * @return list<string>
     */
    public function getParentClassNames(string $fqcn): array
    {
        try {
            $class = $this->reflector->reflectClass($fqcn);
        } catch (IdentifierNotFound | ParseToAstFailure) {
            return [];
        }

        $parents = [];

        try {
            $parent = $class->getParentClass();
        } catch (IdentifierNotFound | ParseToAstFailure) {
            return $parents;
        }

        while (null !== $parent) {
            $parents[] = $parent->getName();
            try {
                $parent = $parent->getParentClass();
            } catch (IdentifierNotFound | ParseToAstFailure) {
                break;
            }
        }

        return $parents;
    }

    /**
     * Get all interface names including those inherited from parent classes.
     *
     * @return list<string>
     */
    public function getInterfaceNames(string $fqcn): array
    {
        try {
            $class = $this->reflector->reflectClass($fqcn);
        } catch (IdentifierNotFound | ParseToAstFailure) {
            return [];
        }

        try {
            return $class->getInterfaceNames();
        } catch (IdentifierNotFound | ParseToAstFailure) {
            return [];
        }
    }

    /**
     * Get all trait names including those from parent classes.
     *
     * @return list<string>
     */
    public function getTraitNames(string $fqcn): array
    {
        try {
            $class = $this->reflector->reflectClass($fqcn);
        } catch (IdentifierNotFound | ParseToAstFailure) {
            return [];
        }

        $allTraits = [];
        $current = $class;
        while (null !== $current) {
            try {
                foreach ($current->getTraitNames() as $traitName) {
                    $allTraits[] = $traitName;
                }
                $current = $current->getParentClass();
            } catch (IdentifierNotFound | ParseToAstFailure) {
                break;
            }
        }

        return $allTraits;
    }
}
