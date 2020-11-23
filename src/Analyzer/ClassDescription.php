<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescription
{
    private FullyQualifiedClassName $FQCN;
    private array $dependencies;
    private array $interfaces;
    private string $fullPath;

    public function __construct(string $fullPath, FullyQualifiedClassName $FQCN, array $dependencies, array $interfaces)
    {
        $this->FQCN = $FQCN;
        $this->dependencies = $dependencies;
        $this->interfaces = $interfaces;
        $this->fullPath = $fullPath;
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

    public function dependsOnly(string $pattern): bool
    {
        $depends = function (ClassDependency $dependency) use ($pattern) {
            return !$dependency->getFQCN()->namespaceMatches($pattern);
        };

        $externalDep = \count(array_filter($this->dependencies, $depends));

        return $externalDep > 0;
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

    public function fullPath(): string
    {
        return $this->fullPath;
    }
}
