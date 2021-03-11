<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescription
{
    private FullyQualifiedClassName $FQCN;
    private array $dependencies;
    private array $interfaces;
    private string $fullPath;

    public function __construct(FullyQualifiedClassName $FQCN, array $dependencies, array $interfaces)
    {
        $this->FQCN = $FQCN;
        $this->dependencies = $dependencies;
        $this->interfaces = $interfaces;
    }

    public function setFullPath(string $fullPath): void
    {
        $this->fullPath = $fullPath;
    }

    public static function build(string $FQCN): ClassDescriptionBuilder
    {
        return ClassDescriptionBuilder::create($FQCN);
    }

    public function getName(): string
    {
        return $this->FQCN->className();
    }

    public function getFQCN(): string
    {
        return $this->FQCN->toString();
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

    public function nameMatches(string $pattern): bool
    {
        return $this->FQCN->classMatches($pattern);
    }

    public function namespaceMatches(string $pattern): bool
    {
        return $this->FQCN->matches($pattern);
    }

    public function fullPath(): string
    {
        return $this->fullPath;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }
}
