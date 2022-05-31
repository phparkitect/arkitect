<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescription
{
    /** @var FullyQualifiedClassName */
    private $FQCN;

    /** @var array */
    private $dependencies;

    /** @var array */
    private $interfaces;

    /** @var string */
    private $fullPath;
    /**
     * @var ?FullyQualifiedClassName
     */
    private $extends;

    /** @var bool */
    private $final;

    /** @var bool */
    private $abstract;

    /** @var string */
    private $docBlock;

    public function __construct(
        FullyQualifiedClassName $FQCN,
        array $dependencies,
        array $interfaces,
        ?FullyQualifiedClassName $extends,
        bool $final,
        bool $abstract,
        string $docBlock = ''
    ) {
        $this->FQCN = $FQCN;
        $this->dependencies = $dependencies;
        $this->interfaces = $interfaces;
        $this->extends = $extends;
        $this->final = $final;
        $this->abstract = $abstract;
        $this->fullPath = '';
        $this->docBlock = $docBlock;
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
        $depends = function (ClassDependency $dependency) use ($pattern): bool {
            return $dependency->getFQCN()->matches($pattern);
        };

        return (bool) \count(array_filter($this->dependencies, $depends));
    }

    public function dependsOnNamespace(string $pattern): bool
    {
        $depends = function (ClassDependency $dependency) use ($pattern): bool {
            return $dependency->getFQCN()->namespaceMatches($pattern);
        };

        return (bool) \count(array_filter($this->dependencies, $depends));
    }

    public function dependsOn(string $pattern): bool
    {
        $depends = function (ClassDependency $dependency) use ($pattern): bool {
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

    public function namespaceMatchesOneOfTheseNamespaces(array $classesToBeExcluded): bool
    {
        foreach ($classesToBeExcluded as $classToBeExcluded) {
            if ($this->namespaceMatches($classToBeExcluded)) {
                return true;
            }
        }

        return false;
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

    public function getExtends(): ?FullyQualifiedClassName
    {
        return $this->extends;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function getDocBlock(): string
    {
        return $this->docBlock;
    }

    public function containsDocBlock(string $search): bool
    {
        if (str_contains($this->docBlock, $search)) {
            return true;
        }

        return false;
    }
}
