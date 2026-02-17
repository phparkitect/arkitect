<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescription
{
    private FullyQualifiedClassName $FQCN;

    private string $filePath;

    /** @var list<ClassDependency> */
    private array $dependencies;

    /** @var list<FullyQualifiedClassName> */
    private array $interfaces;

    /** @var list<FullyQualifiedClassName> */
    private array $extends;

    /** @var list<string> */
    private array $docBlock;

    /** @var list<FullyQualifiedClassName> */
    private array $attributes;

    /** @var list<FullyQualifiedClassName> */
    private array $traits;

    private bool $final;

    private bool $readonly;

    private bool $abstract;

    private bool $interface;

    private bool $trait;

    private bool $enum;

    /**
     * @param list<ClassDependency>         $dependencies
     * @param list<FullyQualifiedClassName> $interfaces
     * @param list<FullyQualifiedClassName> $extends
     * @param list<string>                  $docBlock
     * @param list<FullyQualifiedClassName> $attributes
     * @param list<FullyQualifiedClassName> $traits
     */
    public function __construct(
        FullyQualifiedClassName $FQCN,
        array $dependencies,
        array $interfaces,
        array $extends,
        bool $final,
        bool $readonly,
        bool $abstract,
        bool $interface,
        bool $trait,
        bool $enum,
        array $docBlock,
        array $attributes,
        array $traits,
        string $filePath,
    ) {
        $this->FQCN = $FQCN;
        $this->filePath = $filePath;
        $this->dependencies = $dependencies;
        $this->interfaces = $interfaces;
        $this->extends = $extends;
        $this->final = $final;
        $this->readonly = $readonly;
        $this->abstract = $abstract;
        $this->docBlock = $docBlock;
        $this->attributes = $attributes;
        $this->traits = $traits;
        $this->interface = $interface;
        $this->trait = $trait;
        $this->enum = $enum;
    }

    public static function getBuilder(string $FQCN, string $filePath): ClassDescriptionBuilder
    {
        $cb = new ClassDescriptionBuilder();
        $cb->setClassName($FQCN);
        $cb->setFilePath($filePath);

        return $cb;
    }

    public function getName(): string
    {
        return $this->FQCN->className();
    }

    /** @return class-string */
    public function getFQCN(): string
    {
        /** @var class-string $fqcn */
        $fqcn = $this->FQCN->toString();

        return $fqcn;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function namespaceMatches(string $pattern): bool
    {
        return $this->FQCN->matches($pattern);
    }

    public function namespaceMatchesExactly(string $namespace): bool
    {
        return $this->FQCN->namespace() === $namespace;
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

    /**
     * @return list<ClassDependency>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return list<FullyQualifiedClassName>
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @return list<FullyQualifiedClassName>
     */
    public function getExtends(): array
    {
        return $this->extends;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function isInterface(): bool
    {
        return $this->interface;
    }

    public function isTrait(): bool
    {
        return $this->trait;
    }

    public function isEnum(): bool
    {
        return $this->enum;
    }

    public function getDocBlock(): array
    {
        return $this->docBlock;
    }

    public function containsDocBlock(string $search): bool
    {
        foreach ($this->docBlock as $docBlock) {
            if (str_contains($docBlock, $search)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<FullyQualifiedClassName>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $pattern): bool
    {
        return array_reduce(
            $this->attributes,
            static fn (bool $carry, FullyQualifiedClassName $attribute): bool => $carry || $attribute->matches($pattern),
            false
        );
    }

    /**
     * @return list<FullyQualifiedClassName>
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    public function hasTrait(string $pattern): bool
    {
        return array_reduce(
            $this->traits,
            static fn (bool $carry, FullyQualifiedClassName $trait): bool => $carry || $trait->matches($pattern),
            false
        );
    }
}
