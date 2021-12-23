<?php
declare(strict_types=1);

namespace Arkitect\Architecture;

use Arkitect\Architecture\DSL\Component\Component;
use Arkitect\Architecture\DSL\Component\DefinedBy;
use Arkitect\Architecture\DSL\Component\MayDependOnAnyComponent;
use Arkitect\Architecture\DSL\Component\MayDependOnComponents;
use Arkitect\Architecture\DSL\Component\Rules;
use Arkitect\Architecture\DSL\Component\ShouldNotDependOnAnyComponent;
use Arkitect\Architecture\DSL\Component\Where;
use Arkitect\Architecture\DSL\Layered\Layer;
use Arkitect\Architecture\DSL\Modular\Module;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

class Architecture implements Component, DefinedBy, Where, MayDependOnComponents, MayDependOnAnyComponent, ShouldNotDependOnAnyComponent, Rules
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

    public static function withComponents(): Component
    {
        return new self();
    }

    public function component(string $name): DefinedBy
    {
        $this->componentName = $name;

        return $this;
    }

    public function definedBy(string $selector)
    {
        $this->componentSelectors[$this->componentName] = $selector;
        $this->allowedDependencies[$this->componentName] = [];

        return $this;
    }

    public function where(string $componentName)
    {
        $this->componentName = $componentName;

        return $this;
    }

    public function shouldNotDependOnAnyComponent()
    {
        $this->allowedDependencies[$this->componentName] = [];

        return $this;
    }

    public function mayDependOnComponents(string ...$componentNames)
    {
        $this->allowedDependencies[$this->componentName] = $componentNames;

        return $this;
    }

    public function mayDependOnAnyComponent()
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

    public function rules(): iterable
    {
        return $this->rulesBecause('of component architecture');
    }
}
