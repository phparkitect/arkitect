<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescription
{
    /** @var FullyQualifiedClassName */
    private $FQCN;

    /** @var list<ClassDependency> */
    private $dependencies;

    /** @var list<FullyQualifiedClassName> */
    private $interfaces;

    /** @var ?FullyQualifiedClassName */
    private $extends;

    /** @var bool */
    private $final;

    /** @var bool */
    private $abstract;

    /** @var bool */
    private $interface;

    /** @var list<string> */
    private $docBlock;

    /** @var list<FullyQualifiedClassName> */
    private $attributes;

    /**
     * @param list<ClassDependency>         $dependencies
     * @param list<FullyQualifiedClassName> $interfaces
     * @param ?FullyQualifiedClassName      $extends
     * @param list<FullyQualifiedClassName> $attributes
     */
    public function __construct(
        FullyQualifiedClassName $FQCN,
        array $dependencies,
        array $interfaces,
        ?FullyQualifiedClassName $extends,
        bool $final,
        bool $abstract,
        bool $interface,
        array $docBlock = [],
        array $attributes = []
    ) {
        $this->FQCN = $FQCN;
        $this->dependencies = $dependencies;
        $this->interfaces = $interfaces;
        $this->extends = $extends;
        $this->final = $final;
        $this->abstract = $abstract;
        $this->docBlock = $docBlock;
        $this->attributes = $attributes;
        $this->interface = $interface;
    }

    public static function getBuilder(string $FQCN): ClassDescriptionBuilder
    {
        $cb = new ClassDescriptionBuilder();
        $cb->setClassName($FQCN);

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

    public function isInterface(): bool
    {
        return $this->interface;
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
