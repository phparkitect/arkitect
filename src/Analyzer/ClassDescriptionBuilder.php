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
        $this->classDependencies[$cd->getFQCN()->toString()] = $cd;

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
        $cd = new ClassDescription($this->FQCN, $this->classDependencies, $this->interfaces, $this->extend);
        $cd->setFullPath($this->filePath);

        return $cd;
    }
}
