<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

final class ClassDependencyCollection implements \IteratorAggregate
{
    /**
     * @var array<ClassDependency>
     */
    private $classDependencyList;

    public function __construct(ClassDependency ...$classDependencyList)
    {
        $this->classDependencyList = $classDependencyList;
    }

    /**
     * @return \Iterator<array-key, ClassDependency>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->classDependencyList);
    }

    public function removeDuplicateDependencies(): self
    {
        $filteredList = [];
        foreach ($this->classDependencyList as $classDependency) {
            $dependencyFqcn = $classDependency->getFQCN()->toString();
            if (\array_key_exists($dependencyFqcn, $filteredList)) {
                continue;
            }
            $filteredList[$dependencyFqcn] = $classDependency;
        }

        return new self(...array_values($filteredList));
    }
}
