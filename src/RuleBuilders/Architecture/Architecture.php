<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

use Arkitect\Expression\Boolean\Andx;
use Arkitect\Expression\Boolean\Not;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

class Architecture implements Component, DefinedBy, Where, MayDependOnComponents, MayDependOnAnyComponent, ShouldNotDependOnAnyComponent, ShouldOnlyDependOnComponents, Rules
{
    /** @var string */
    private $componentName;
    /** @var array<string, string> */
    private $componentSelectors;
    /** @var array<string, string[]> */
    private $allowedDependencies;
    /** @var array<string, string[]> */
    private $componentDependsOnlyOnTheseNamespaces;

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
        $this->componentDependsOnlyOnTheseNamespaces[$this->componentName] = $componentNames;

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

    public function rules(): iterable
    {
        $layerNames = array_keys($this->componentSelectors);

        foreach ($this->componentSelectors as $name => $selector) {
            if (isset($this->allowedDependencies[$name])) {
                $forbiddenComponents = array_diff($layerNames, [$name], $this->allowedDependencies[$name]);

                if (!empty($forbiddenComponents)) {
                    yield Rule::allClasses()
                        ->that(\is_string($selector) ? new ResideInOneOfTheseNamespaces($selector) : $selector)
                        ->should($this->createForbiddenExpression($forbiddenComponents))
                        ->because('of component architecture');
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
                ->because('of component architecture');
        }
    }

    public function createForbiddenExpression(array $forbiddenComponents): Expression
    {
        $forbiddenNamespaceSelectors = array_filter(
            array_map(function (string $componentName): ?string {
                $selector = $this->componentSelectors[$componentName];

                return \is_string($selector) ? $selector : null;
            }, $forbiddenComponents)
        );

        $forbiddenExpressionSelectors = array_filter(
            array_map(function (string $componentName): ?Expression {
                $selector = $this->componentSelectors[$componentName];

                return \is_string($selector) ? null : $selector;
            }, $forbiddenComponents)
        );

        $forbiddenExpressionList = [];
        if ([] !== $forbiddenNamespaceSelectors) {
            $forbiddenExpressionList[] = new NotDependsOnTheseNamespaces(...$forbiddenNamespaceSelectors);
        }
        if ([] !== $forbiddenExpressionSelectors) {
            $forbiddenExpressionList[] = new Not(new Andx(...$forbiddenExpressionSelectors));
        }

        return 1 === \count($forbiddenExpressionList)
            ? array_pop($forbiddenExpressionList)
            : new Andx(...$forbiddenExpressionList);
    }
}
