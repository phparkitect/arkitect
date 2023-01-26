<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Webmozart\Assert\Assert;

class ClassDescriptionBuilder
{
    /** @var list<ClassDependency> */
    private $classDependencies = [];

    /** @var ?FullyQualifiedClassName */
    private $FQCN = null;

    /** @var list<FullyQualifiedClassName> */
    private $interfaces = [];

    /** @var ?FullyQualifiedClassName */
    private $extend = null;

    /** @var bool */
    private $final = false;

    /** @var bool */
    private $abstract = false;

    /** @var list<string> */
    private $docBlock = [];

    /** @var list<FullyQualifiedClassName> */
    private $attributes = [];

    /** @var bool */
    private $interface = false;

    public function setClassName(string $FQCN): void
    {
        $this->FQCN = FullyQualifiedClassName::fromString($FQCN);
    }

    public function clear(): void
    {
        $this->FQCN = null;
        $this->classDependencies = [];
        $this->interfaces = [];
        $this->final = false;
        $this->abstract = false;
        $this->docBlock = [];
        $this->attributes = [];
        $this->interface = false;
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

    public function get(): ClassDescription
    {
        Assert::notNull($this->FQCN);

        return new ClassDescription(
            $this->FQCN,
            $this->classDependencies,
            $this->interfaces,
            $this->extend,
            $this->final,
            $this->abstract,
            $this->interface,
            $this->docBlock,
            $this->attributes
        );
    }

    public function setFinal(bool $final): self
    {
        $this->final = $final;

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
}
