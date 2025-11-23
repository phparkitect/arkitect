<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class BeUsedOnlyBy implements Expression
{
    /** @var array<string> */
    private array $allowedNamespaces;

    /**
     * Static registry mapping class FQCNs to their usages.
     * Format: ['TargetClass' => [['fqcn' => 'UserClass', 'line' => 10, 'filePath' => '...'], ...]]
     *
     * @var array<string, array<array{fqcn: string, line: int, filePath: string}>>
     */
    private static array $usageMap = [];

    /**
     * @param array<string> $allowedNamespaces Namespaces/classes that are allowed to use the target classes
     */
    public function __construct(array $allowedNamespaces)
    {
        $this->allowedNamespaces = $allowedNamespaces;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $desc = implode(', ', $this->allowedNamespaces);

        return new Description("should be used only by classes in: $desc", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $targetFqcn = $theClass->getFQCN();

        if (!isset(self::$usageMap[$targetFqcn])) {
            return;
        }

        foreach (self::$usageMap[$targetFqcn] as $usage) {
            $userFqcn = FullyQualifiedClassName::fromString($usage['fqcn']);

            if (!$this->matchesAllowedNamespaces($userFqcn)) {
                $violation = Violation::createWithErrorLine(
                    $theClass->getFQCN(),
                    ViolationMessage::withDescription(
                        $this->describe($theClass, $because),
                        "is used by {$usage['fqcn']}"
                    ),
                    $usage['line'],
                    $usage['filePath']
                );

                $violations->add($violation);
            }
        }
    }

    /**
     * Register a class's dependencies in the usage map.
     * This should be called for all classes to build the reverse dependency map.
     */
    public static function registerClassDependencies(ClassDescription $classDescription): void
    {
        $userFqcn = $classDescription->getFQCN();
        $filePath = $classDescription->getFilePath();

        foreach ($classDescription->getDependencies() as $dependency) {
            $targetFqcn = $dependency->getFQCN()->toString();

            if (!isset(self::$usageMap[$targetFqcn])) {
                self::$usageMap[$targetFqcn] = [];
            }

            self::$usageMap[$targetFqcn][] = [
                'fqcn' => $userFqcn,
                'line' => $dependency->getLine(),
                'filePath' => $filePath,
            ];
        }
    }

    /**
     * Clear the usage map. Useful for testing or resetting state between runs.
     */
    public static function clearUsageMap(): void
    {
        self::$usageMap = [];
    }

    /**
     * Get the current usage map. Useful for debugging.
     *
     * @return array<string, array<array{fqcn: string, line: int, filePath: string}>>
     */
    public static function getUsageMap(): array
    {
        return self::$usageMap;
    }

    private function matchesAllowedNamespaces(FullyQualifiedClassName $fqcn): bool
    {
        foreach ($this->allowedNamespaces as $allowedNamespace) {
            $namespace = preg_quote($allowedNamespace, '#');
            $namespace = str_replace('\\*', '.*', $namespace);

            if (1 === preg_match("#^{$namespace}#", $fqcn->toString())) {
                return true;
            }
        }

        return false;
    }
}
