<?php
declare(strict_types=1);

namespace Arkitect\Architecture;

use Arkitect\Architecture\DSL\Layered\Layer;
use Arkitect\Architecture\DSL\Modular\Module;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

class Architecture
{
    /** @var string */
    private $componentName;
    /** @var array<string, string> */
    private $componentSelectors;
    /** @var array<string, string[]> */
    private $allowedDependencies;

    private function __construct()
    {
    }

    public static function withModules(): Module
    {
        return new ModularArchitecture(new self());
    }

    public static function withLayers(): Layer
    {
        return new LayeredArchitecture(new self());
    }

    public function component(string $name): self
    {
        $this->componentName = $name;

        return $this;
    }

    public function definedBy(string $selector): self
    {
        $this->componentSelectors[$this->componentName] = $selector;
        $this->allowedDependencies[$this->componentName] = [];

        return $this;
    }

    public function where(string $layerName): self
    {
        $this->componentName = $layerName;

        return $this;
    }

    public function mayNotDependOnAnyComponent(): self
    {
        $this->allowedDependencies[$this->componentName] = [];

        return $this;
    }

    public function mayDependOnComponents(string ...$componentNames): self
    {
        $this->allowedDependencies[$this->componentName] = $componentNames;

        return $this;
    }

    public function mayDependOnAnyComponent(): self
    {
        $this->allowedDependencies[$this->componentName] = array_keys($this->componentSelectors);

        return $this;
    }

    public function rulesBecause(string $reason): iterable
    {
        $layerNames = array_keys($this->componentSelectors);

        foreach ($this->componentSelectors as $name => $selector) {
            $forbiddenComponents = array_diff($layerNames, [$name], $this->allowedDependencies[$name]);

            $forbiddenSelectors = array_map(function (string $componentName): string {
                return $this->componentSelectors[$componentName];
            }, $forbiddenComponents);

            yield Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces($selector))
                ->should(new NotDependsOnTheseNamespaces(...$forbiddenSelectors))
                ->because($reason);
        }
    }
}
