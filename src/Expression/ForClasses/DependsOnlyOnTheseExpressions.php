<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\MergeableExpression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class DependsOnlyOnTheseExpressions implements Expression
{
    /** @var FileParser */
    private $fileParser;

    /** @var Expression[] */
    private $expressions = [];

    public function __construct(Expression ...$expressions)
    {
        $this->fileParser = FileParserFactory::createFileParser();
        foreach ($expressions as $newExpression) {
            $this->addExpression($newExpression);
        }
    }

    public function describe(ClassDescription $theClass, string $because = ''): Description
    {
        $expressionsDescriptions = '';
        foreach ($this->expressions as $expression) {
            $expressionsDescriptions .= $expression->describe($theClass, '')->toString()."\n";
        }

        return new Description(
            "should depend only on classes in one of the given expressions: \n"
            .$expressionsDescriptions,
            $because
        );
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because = ''): void
    {
        $dependencies = $this->removeDuplicateDependencies($theClass->getDependencies());

        foreach ($dependencies as $dependency) {
            if (
                '' === $dependency->getFQCN()->namespace()
                || $theClass->namespaceMatches($dependency->getFQCN()->namespace())
            ) {
                continue;
            }

            $dependencyClassDescription = $this->getDependencyClassDescription($dependency);
            if (null === $dependencyClassDescription) {
                return;
            }

            if (!$this->matchesAnyOfTheExpressions($dependencyClassDescription)) {
                $violations->add(
                    Violation::create(
                        $theClass->getFQCN(),
                        ViolationMessage::withDescription(
                            $this->describe($theClass, $because),
                            "The dependency '".$dependencyClassDescription->getFQCN()."' violated the expression: \n"
                            .$this->describeDependencyRequirement($dependencyClassDescription)."\n"
                        )
                    )
                );
            }
        }
    }

    private function describeDependencyRequirement(ClassDescription $theDependency): string
    {
        $expressionsDescriptions = [];
        foreach ($this->expressions as $expression) {
            $expressionsDescriptions[] = $expression->describe($theDependency, '')->toString();
        }

        return implode("\nOR\n", array_unique($expressionsDescriptions));
    }

    private function matchesAnyOfTheExpressions(ClassDescription $dependencyClassDescription): bool
    {
        foreach ($this->expressions as $expression) {
            $newViolations = new Violations();
            $expression->evaluate($dependencyClassDescription, $newViolations, '');
            if (0 === $newViolations->count()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassDependency $dependency
     */
    private function getDependencyClassDescription($dependency): ?ClassDescription
    {
        /** @var class-string $dependencyFqcn */
        $dependencyFqcn = $dependency->getFQCN()->toString();
        $reflector = new \ReflectionClass($dependencyFqcn);
        $filename = $reflector->getFileName();
        if (false === $filename) {
            return null;
        }
        $this->fileParser->parse(file_get_contents($filename), $filename);
        $classDescriptionList = $this->fileParser->getClassDescriptions();

        return array_pop($classDescriptionList);
    }

    /**
     * @param ClassDependency[] $dependenciesList
     *
     * @return ClassDependency[]
     */
    private function removeDuplicateDependencies(array $dependenciesList): array
    {
        $filteredList = [];
        foreach ($dependenciesList as $classDependency) {
            $dependencyFqcn = $classDependency->getFQCN()->toString();
            if (\array_key_exists($dependencyFqcn, $filteredList)) {
                continue;
            }
            $filteredList[$dependencyFqcn] = $classDependency;
        }

        return $filteredList;
    }

    private function addExpression(Expression $newExpression): void
    {
        foreach ($this->expressions as $index => $existingExpression) {
            if (
                $newExpression instanceof $existingExpression
                && $newExpression instanceof MergeableExpression
                && $existingExpression instanceof MergeableExpression
            ) {
                $this->expressions[$index] = $existingExpression->mergeWith($newExpression);

                return;
            }
        }
        $this->expressions[] = $newExpression;
    }
}
