<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Webmozart\Assert\Assert;

class ClassDescriptionBuilder
{
    /** @var list<ClassDependency> */
    private array $classDependencies = [];

    private ?FullyQualifiedClassName $FQCN = null;

    /** @var list<FullyQualifiedClassName> */
    private array $interfaces = [];

    private ?FullyQualifiedClassName $extend = null;

    private bool $final = false;

    private bool $readonly = false;

    private bool $abstract = false;

    /** @var list<string> */
    private array $docBlock = [];

    /** @var list<FullyQualifiedClassName> */
    private array $attributes = [];

    private bool $interface = false;

    private bool $trait = false;

    private bool $enum = false;

    public function clear(): void
    {
        $this->FQCN = null;
        $this->classDependencies = [];
        $this->interfaces = [];
        $this->extend = null;
        $this->final = false;
        $this->readonly = false;
        $this->abstract = false;
        $this->docBlock = [];
        $this->attributes = [];
        $this->interface = false;
        $this->trait = false;
        $this->enum = false;
    }

    public function setClassName(string $FQCN): self
    {
        $this->FQCN = FullyQualifiedClassName::fromString($FQCN);

        return $this;
    }

    public function addInterface(string $FQCN, int $line): self
    {
        $this->addDependency(new ClassDependency($FQCN, $line));
        $this->interfaces[] = FullyQualifiedClassName::fromString($FQCN);

        return $this;
    }

    public function addDependency(ClassDependency $cd): self
    {
        $this->classDependencies[] = $cd;

        return $this;
    }

    public function setExtends(string $FQCN, int $line): self
    {
        $this->addDependency(new ClassDependency($FQCN, $line));
        $this->extend = FullyQualifiedClassName::fromString($FQCN);

        return $this;
    }

    public function setFinal(bool $final): self
    {
        $this->final = $final;

        return $this;
    }

    public function setReadonly(bool $readonly): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function setAbstract(bool $abstract): self
    {
        $this->abstract = $abstract;

        return $this;
    }

    public function setInterface(bool $interface): self
    {
        $this->interface = $interface;

        return $this;
    }

    public function setTrait(bool $trait): self
    {
        $this->trait = $trait;

        return $this;
    }

    public function setEnum(bool $enum): self
    {
        $this->enum = $enum;

        return $this;
    }

    public function addDocBlock(string $docBlock): self
    {
        $this->docBlock[] = $docBlock;

        return $this;
    }

    public function addAttribute(string $FQCN, int $line): self
    {
        $this->addDependency(new ClassDependency($FQCN, $line));
        $this->attributes[] = FullyQualifiedClassName::fromString($FQCN);

        return $this;
    }

    public function build(): ClassDescription
    {
        Assert::notNull($this->FQCN, 'You must set an FQCN');

        return new ClassDescription(
            $this->FQCN,
            $this->classDependencies,
            $this->interfaces,
            $this->extend,
            $this->final,
            $this->readonly,
            $this->abstract,
            $this->interface,
            $this->trait,
            $this->enum,
            $this->docBlock,
            $this->attributes
        );
    }
}
