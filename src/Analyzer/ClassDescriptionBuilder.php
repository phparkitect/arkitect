<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescriptionBuilder
{
    private ?array $classDependencies;

    private ?\Arkitect\Analyzer\FullyQualifiedClassName $FQCN;

    private ?string $filePath;

    private ?array $interfaces;

    private function __construct()
    {
    }

    public static function create(string $FQCN, string $filePath): self
    {
        $cdb = new self();
        $cdb->FQCN = FullyQualifiedClassName::fromString($FQCN);
        $cdb->filePath = $filePath;
        $cdb->classDependencies = [];
        $cdb->interfaces = [];

        return $cdb;
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

    public function get(): ClassDescription
    {
        return new ClassDescription(
            $this->FQCN,
            $this->classDependencies,
            $this->interfaces
        );
    }
}
