<?php


namespace Arkitect\Analyzer;


class ClassDescriptionBuilder
{
    private $classDependencies;

    private $FQCN;

    private $filePath;

    private $interfaces;

    private function __construct()
    {
    }

    public static function create(string $FQCN, string $filePath): ClassDescriptionBuilder
    {
        $cdb = new self;
        $cdb->FQCN = FullyQualifiedClassName::fromString($FQCN);
        $cdb->filePath = $filePath;
        $cdb->classDependencies = [];
        $cdb->interfaces = [];

        return $cdb;
    }

    public function addInterface(string $FQCN, int $line): ClassDescriptionBuilder
    {
        $this->addDependency(new ClassDependency($FQCN, $line));
        $this->interfaces[] = FullyQualifiedClassName::fromString($FQCN);

        return $this;
    }

    public function addDependency(ClassDependency $cd): ClassDescriptionBuilder
    {
        $this->classDependencies[] = $cd;

        return $this;
    }

    public function get(): ClassDescription
    {
        return new ClassDescription(
            $this->filePath,
            $this->FQCN,
            $this->classDependencies,
            $this->interfaces
        );
    }
}