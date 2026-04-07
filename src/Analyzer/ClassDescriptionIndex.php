<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class ClassDescriptionIndex
{
    /** @var array<string, ParserResult> keyed by relative file path */
    private array $results = [];

    /** @var array<string, ClassDescription> keyed by FQCN */
    private array $index = [];

    public function add(string $relativeFilePath, ParserResult $result): void
    {
        $this->results[$relativeFilePath] = $result;

        foreach ($result->classDescriptions() as $classDescription) {
            $this->index[$classDescription->getFQCN()] = $classDescription;
        }
    }

    public function enrich(): void
    {
        /** @var array<string, list<ClassDependency>> $resolvedDeps */
        $resolvedDeps = [];
        /** @var array<string, true> $visiting */
        $visiting = [];

        foreach (array_keys($this->index) as $fqcn) {
            $extra = $this->resolveDeps($fqcn, $resolvedDeps, $visiting);

            if ([] !== $extra) {
                $this->index[$fqcn] = $this->index[$fqcn]->withAdditionalDependencies($extra);
            }
        }
    }

    public function get(string $fqcn): ?ClassDescription
    {
        return $this->index[$fqcn] ?? null;
    }

    /**
     * Returns the enriched ClassDescriptions for all classes defined in the given file.
     *
     * @return iterable<ClassDescription>
     */
    public function getClassDescriptionsFor(string $relativeFilePath): iterable
    {
        $result = $this->results[$relativeFilePath] ?? null;

        if (null === $result) {
            return;
        }

        foreach ($result->classDescriptions() as $classDescription) {
            yield $this->index[$classDescription->getFQCN()] ?? $classDescription;
        }
    }

    public function getParsingErrorsFor(string $relativeFilePath): ParsingErrors
    {
        return $this->results[$relativeFilePath]?->parsingErrors() ?? new ParsingErrors();
    }

    /**
     * @param array<string, list<ClassDependency>> $resolvedDeps
     * @param array<string, true>                  $visiting
     *
     * @return list<ClassDependency>
     */
    private function resolveDeps(string $fqcn, array &$resolvedDeps, array &$visiting): array
    {
        if (isset($visiting[$fqcn])) {
            return [];
        }

        if (isset($resolvedDeps[$fqcn])) {
            return $resolvedDeps[$fqcn];
        }

        $visiting[$fqcn] = true;

        $cd = $this->index[$fqcn] ?? null;

        if (null === $cd) {
            unset($visiting[$fqcn]);

            return [];
        }

        /** @var array<string, ClassDependency> $extra keyed by FQCN for dedup */
        $extra = [];

        foreach ($cd->getExtensionPoints() as $ep) {
            $epCd = $this->index[$ep] ?? null;

            if (null !== $epCd) {
                foreach ($epCd->getDependencies() as $dep) {
                    $extra[$dep->getFQCN()->toString()] ??= $dep;
                }
            }

            foreach ($this->resolveDeps($ep, $resolvedDeps, $visiting) as $dep) {
                $extra[$dep->getFQCN()->toString()] ??= $dep;
            }
        }

        unset($visiting[$fqcn]);

        $resolvedDeps[$fqcn] = array_values($extra);

        return $resolvedDeps[$fqcn];
    }
}
