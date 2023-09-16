<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

use Arkitect\Expression\Boolean\Orx;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseExpressions;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

class Architecture implements Component, DefinedBy, Where, MayDependOnComponents, MayDependOnAnyComponent, ShouldNotDependOnAnyComponent, ShouldOnlyDependOnComponents, Rules
{
    /** @var string */
    private $componentName;
    /** @var array<string, Expression|string> */
    private $componentSelectors;
    /** @var array<string, string[]> */
    private $allowedDependencies;
    /** @var array<string, string[]> */
    private $componentDependsOnlyOnTheseComponents;

    private function __construct()
    {
        $this->componentName = '';
        $this->componentSelectors = [];
        $this->allowedDependencies = [];
        $this->componentDependsOnlyOnTheseComponents = [];
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

        return $this;
    }

    public function definedByExpression(Expression $selector)
    {
        $this->componentSelectors[$this->componentName] = $selector;

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

    public function shouldOnlyDependOnComponents(string ...$componentNames)
    {
        $this->componentDependsOnlyOnTheseComponents[$this->componentName] = $componentNames;

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

    public function rules(string $because = 'of component architecture'): iterable
    {
        foreach ($this->componentSelectors as $name => $selector) {
            if (isset($this->allowedDependencies[$name])) {
                yield Rule::allClasses()
                    ->that(\is_string($selector) ? new ResideInOneOfTheseNamespaces($selector) : $selector)
                    ->should($this->createAllowedExpression(
                        array_merge([$name], $this->allowedDependencies[$name])
                    ))
                    ->because($because);
            }

            if (isset($this->componentDependsOnlyOnTheseComponents[$name])) {
                yield Rule::allClasses()
                    ->that(\is_string($selector) ? new ResideInOneOfTheseNamespaces($selector) : $selector)
                    ->should($this->createAllowedExpression($this->componentDependsOnlyOnTheseComponents[$name]))
                    ->because($because);
            }
        }
    }

    private function createAllowedExpression(array $components): Expression
    {
        $namespaceSelectors = $this->extractComponentsNamespaceSelectors($components);

        $expressionSelectors = $this->extractComponentExpressionSelectors($components);

        if ([] === $namespaceSelectors && [] === $expressionSelectors) {
            return new Orx(); // always true
        }

        if ([] !== $namespaceSelectors) {
            $expressionSelectors[] = new ResideInOneOfTheseNamespaces(...$namespaceSelectors);
        }

        return new DependsOnlyOnTheseExpressions(...$expressionSelectors);
    }

    private function extractComponentsNamespaceSelectors(array $components): array
    {
        return array_filter(
            array_map(function (string $componentName): ?string {
                $selector = $this->componentSelectors[$componentName];

                return \is_string($selector) ? $selector : null;
            }, $components)
        );
    }

    private function extractComponentExpressionSelectors(array $components): array
    {
        return array_filter(
            array_map(function (string $componentName): ?Expression {
                $selector = $this->componentSelectors[$componentName];

                return \is_string($selector) ? null : $selector;
            }, $components)
        );
    }
}
