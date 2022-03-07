<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescriptionBuilder
{
    /** @var array */
    private $classDependencies;

    /** @var FullyQualifiedClassName */
    private $FQCN;

    /** @var array */
    private $interfaces;

    /** @var FullyQualifiedClassName */
    private $extend;

    /** @var string */
    private $filePath;

    /** @var bool */
    private $final;

    /** @var bool */
    private $abstract;

    private function __construct()
    {
    }

    public static function create(string $FQCN): self
    {
        $cdb = new self();
        $cdb->FQCN = FullyQualifiedClassName::fromString($FQCN);
        $cdb->filePath = '';
        $cdb->classDependencies = [];
        $cdb->interfaces = [];
        $cdb->final = false;
        $cdb->abstract = false;

        return $cdb;
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
        $cd = new ClassDescription(
            $this->FQCN,
            $this->classDependencies,
            $this->interfaces,
            $this->extend,
            $this->final,
            $this->abstract
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
}
