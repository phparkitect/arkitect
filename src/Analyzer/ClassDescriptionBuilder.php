<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Webmozart\Assert\Assert;

class ClassDescriptionBuilder
{
    /** @var list<ClassDependency> */
    private $classDependencies;

    /** @var ?FullyQualifiedClassName */
    private $FQCN;

    /** @var list<FullyQualifiedClassName> */
    private $interfaces;

    /** @var ?FullyQualifiedClassName */
    private $extend;

    /** @var string */
    private $filePath;

    /** @var bool */
    private $final;

    /** @var bool */
    private $abstract;

    /** @var list<string> */
    private $docBlock;

    /** @var list<FullyQualifiedClassName> */
    private $attributes;

    /** @var bool */
    private $interface;

    /**
     * @param list<ClassDependency>         $classDependencies
     * @param list<FullyQualifiedClassName> $interfaces
     * @param list<FullyQualifiedClassName> $attributes
     * @param ?FullyQualifiedClassName      $FQCN
     */
    private function __construct(
        ?FullyQualifiedClassName $FQCN,
        string $filePath,
        array $classDependencies,
        array $interfaces,
        bool $final,
        bool $abstract,
        bool $interface,
        array $docBlock = [],
        array $attributes = []
    ) {
        $this->FQCN = $FQCN;
        $this->filePath = $filePath;
        $this->classDependencies = $classDependencies;
        $this->interfaces = $interfaces;
        $this->final = $final;
        $this->abstract = $abstract;
        $this->docBlock = $docBlock;
        $this->attributes = $attributes;
        $this->interface = $interface;
    }

    public static function create(): self
    {
        return new self(
            null,
            '',
            [],
            [],
            false,
            false,
            false,
            [],
            []
        );
    }

    public function setClassName(string $FQCN): void
    {
        $this->FQCN = FullyQualifiedClassName::fromString($FQCN);
    }

    public function clear(): void
    {
        $this->FQCN = null;
        $this->filePath = '';
        $this->classDependencies = [];
        $this->interfaces = [];
        $this->final = false;
        $this->abstract = false;
        $this->docBlock = [];
        $this->attributes = [];
        $this->interface = false;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;

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

    public function get(): ClassDescription
    {
        Assert::notNull($this->FQCN);

        $cd = new ClassDescription(
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
        $cd->setFullPath($this->filePath);

        return $cd;
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
