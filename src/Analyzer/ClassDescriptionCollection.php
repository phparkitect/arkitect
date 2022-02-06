<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Rules\ParsingError;

class ClassDescriptionCollection
{
    /** @var array */
    private $classDescriptions;

    /** @var array */
    private $errors;

    public function __construct()
    {
        $this->classDescriptions = [];
        $this->errors = [];
    }

    public function add(ClassDescription $classDescription): void
    {
        $this->classDescriptions[$classDescription->getFQCN()] = $classDescription;
    }

    public function getClassDescriptions(): array
    {
        return $this->classDescriptions;
    }

    public function getDependencies(string $classDescriptionFQCN, array $dependencies = []): array
    {
        if (!\array_key_exists($classDescriptionFQCN, $this->classDescriptions)) {
            $this->errors[] = ParsingError::create(
                $classDescriptionFQCN,
                'Class not found: '.$classDescriptionFQCN.' into collection'
            );

            return $dependencies;
        }

        /** @var ClassDescription $classDescriptionFiltered */
        $classDescriptionFiltered = $this->classDescriptions[$classDescriptionFQCN];

        $dependenciesFound = $classDescriptionFiltered->getDependencies();

        /** @var ClassDependency $dependency */
        foreach ($dependenciesFound as $dependency) {
            if ($dependency->getFQCN()->toString() === $classDescriptionFQCN ||
                \array_key_exists($dependency->getFQCN()->toString(), $dependencies)) {
                continue;
            }

            $dependencies[$dependency->getFQCN()->toString()] = $dependency;
            $dependencies = $this->getDependencies($dependency->getFQCN()->toString(), $dependencies);
        }

        return $dependencies;
    }

    public function getExtends(string $classDescriptionFQCN, array $extends = []): array
    {
        if (!\array_key_exists($classDescriptionFQCN, $this->classDescriptions)) {
            $this->errors[] = ParsingError::create(
                $classDescriptionFQCN,
                'Class not found: '.$classDescriptionFQCN.' into collection'
            );

            return $extends;
        }

        /** @var ClassDescription $classDescriptionFiltered */
        $classDescriptionFiltered = $this->classDescriptions[$classDescriptionFQCN];

        $extendsFound = $classDescriptionFiltered->getExtends();

        if (null === $extendsFound) {
            return $extends;
        }

        /** @var FullyQualifiedClassName $extendsFoundFQCN */
        $extendsFoundFQCN = $extendsFound;

        if ($extendsFoundFQCN->toString() === $classDescriptionFQCN ||
            \array_key_exists($extendsFoundFQCN->toString(), $extends)) {
            return $extends;
        }

        $extends[$extendsFoundFQCN->toString()] = $extendsFoundFQCN;

        return $this->getExtends($extendsFoundFQCN->toString(), $extends);
    }

    public function getInterfaces(string $classDescriptionFQCN, array $interfaces = []): array
    {
        if (!\array_key_exists($classDescriptionFQCN, $this->classDescriptions)) {
            $this->errors[] = ParsingError::create(
                $classDescriptionFQCN,
                'Class not found: '.$classDescriptionFQCN.' into collection'
            );

            return $interfaces;
        }

        /** @var ClassDescription $classDescriptionFiltered */
        $classDescriptionFiltered = $this->classDescriptions[$classDescriptionFQCN];

        $interfacesFound = $classDescriptionFiltered->getInterfaces();

        /** @var FullyQualifiedClassName $interface */
        foreach ($interfacesFound as $interface) {
            if ($interface->toString() === $classDescriptionFQCN ||
                \array_key_exists($interface->toString(), $interfaces)) {
                continue;
            }

            $interfaces[$interface->toString()] = $interface;
            $interfaces = $this->getInterfaces($interface->toString(), $interfaces);
        }

        return $interfaces;
    }

    public function exists(string $classDescriptionFQCN): bool
    {
        return \array_key_exists($classDescriptionFQCN, $this->classDescriptions);
    }

    public function get(string $FQCN): ?ClassDescription
    {
        if (!$this->exists($FQCN)) {
            $this->errors[] = ParsingError::create(
                $FQCN,
                'Class not found: '.$FQCN.' into collection'
            );

            return null;
        }

        return $this->classDescriptions[$FQCN];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
