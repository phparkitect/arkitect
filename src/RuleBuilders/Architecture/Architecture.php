<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

class Architecture implements Component, DefinedBy, Where, MayDependOnComponents, MayDependOnAnyComponent, ShouldNotDependOnAnyComponent, ShouldOnlyDependOnComponents, Rules
{
    private string $componentName;
    /** @var array<string, string> */
    private array $componentSelectors;
    /** @var array<string, string[]> */
    private array $allowedDependencies;
    /** @var array<string, string[]> */
    private array $componentDependsOnlyOnTheseNamespaces;

    private function __construct()
    {
        $this->componentName = '';
        $this->componentSelectors = [];
        $this->allowedDependencies = [];
        $this->componentDependsOnlyOnTheseNamespaces = [];
    }

    public static function withComponents(): Component
    {
        return new self();
    }

    public function component(string $name): DefinedBy
    {
        $this->componentName = $name;

        return $this;
    }

    public function definedBy(string $selector): static
    {
        $this->componentSelectors[$this->componentName] = $selector;

        return $this;
    }

    public function where(string $componentName): static
    {
        $this->componentName = $componentName;

        return $this;
    }

    public function shouldNotDependOnAnyComponent(): static
    {
        $this->allowedDependencies[$this->componentName] = [];

        return $this;
    }

    public function shouldOnlyDependOnComponents(string ...$componentNames): static
    {
        $this->componentDependsOnlyOnTheseNamespaces[$this->componentName] = $componentNames;

        return $this;
    }

    public function mayDependOnComponents(string ...$componentNames): static
    {
        $this->allowedDependencies[$this->componentName] = $componentNames;

        return $this;
    }

    public function mayDependOnAnyComponent(): static
    {
        $this->allowedDependencies[$this->componentName] = array_keys($this->componentSelectors);

        return $this;
    }

    public function rules(string $because = 'of component architecture'): iterable
    {
        $layerNames = array_keys($this->componentSelectors);

        foreach ($this->componentSelectors as $name => $selector) {
            if (isset($this->allowedDependencies[$name])) {
                $forbiddenComponents = array_diff($layerNames, [$name], $this->allowedDependencies[$name]);

                if (!empty($forbiddenComponents)) {
                    $forbiddenSelectors = array_map(function (string $componentName): string {
                        return $this->componentSelectors[$componentName];
                    }, $forbiddenComponents);

                    yield Rule::allClasses()
                        ->that(new ResideInOneOfTheseNamespaces($selector))
                        ->should(new NotDependsOnTheseNamespaces(...$forbiddenSelectors))
                        ->because($because);
                }
            }

            if (!isset($this->componentDependsOnlyOnTheseNamespaces[$name])) {
                continue;
            }

            $allowedDependencies = array_map(function (string $componentName): string {
                return $this->componentSelectors[$componentName];
            }, $this->componentDependsOnlyOnTheseNamespaces[$name]);

            yield Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces($selector))
                ->should(new DependsOnlyOnTheseNamespaces(...$allowedDependencies))
                ->because($because);
        }
    }
}
