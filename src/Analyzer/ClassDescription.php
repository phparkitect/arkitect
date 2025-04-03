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
     * @param list<FullyQualifiedClassName> $attributes
     * @param list<string>                  $docBlock
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
        string $filePath
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

    public function getFQCN(): string
    {
        return $this->FQCN->toString();
    }

    public function getFilePath(): string
    {
        return $this->filePath;
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
            static function (bool $carry, FullyQualifiedClassName $attribute) use ($pattern): bool {
                return $carry || $attribute->matches($pattern);
            },
            false
        );
    }
}
