<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescription
{
    private $FQCN;

    private $fullPath;

    private $dependencies;

    private $interfaces;

    public function __construct(string $fullPath, FullyQualifiedClassName $FQCN, array $dependencies, array $interfaces)
    {
        $this->FQCN = $FQCN;
        $this->fullPath = $fullPath;
        $this->dependencies = $dependencies;
        $this->interfaces = $interfaces;
    }

    public static function build(string $FQCN, string $filePath): ClassDescriptionBuilder
    {
        return ClassDescriptionBuilder::create($FQCN, $filePath);
    }

    public function getName(): string
    {
        return $this->FQCN->className();
    }

    public function getFQCN(): string
    {
        return $this->FQCN->toString();
    }

    /**
     * @deprecated usare namespaceMatches il cui comportamento è corretto
     */
    public function isInNamespace(string $pattern): bool
    {
        return $this->FQCN->namespaceMatches($pattern);
    }

    public function dependsOnClass(string $pattern): bool
    {
        $depends = function (ClassDependency $dependency) use ($pattern) {
            return $dependency->getFQCN()->matches($pattern);
        };

        return (bool) \count(array_filter($this->dependencies, $depends));
    }

    public function dependsOnNamespace(string $pattern): bool
    {
        $depends = function (ClassDependency $dependency) use ($pattern) {
            return $dependency->getFQCN()->namespaceMatches($pattern);
        };

        return (bool) \count(array_filter($this->dependencies, $depends));
    }

    public function dependsOn(string $pattern): bool
    {
        $depends = function (ClassDependency $dependency) use ($pattern) {
            return $dependency->matches($pattern);
        };

        return (bool) \count(array_filter($this->dependencies, $depends));
    }

    public function dependsOnlyOnClassesMatching(string $pattern): bool
    {
        $depends = function (ClassDependency $dependency) use ($pattern) {
            return !$dependency->getFQCN()->matches($pattern);
        };

        $externalDep = \count(array_filter($this->dependencies, $depends));

        return 0 === $externalDep;
    }

    public function nameMatches(string $pattern): bool
    {
        return $this->FQCN->classMatches($pattern);
    }

    public function namespaceMatches(string $pattern): bool
    {
        return $this->FQCN->matches($pattern);
    }

    public function implements(string $pattern): bool
    {
        $implements = function (FullyQualifiedClassName $FQCN) use ($pattern) {
            return $FQCN->matches($pattern);
        };

        return (bool) \count(array_filter($this->interfaces, $implements));
    }
}
