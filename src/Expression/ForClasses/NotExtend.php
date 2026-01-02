<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Exceptions\InvalidPatternException;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NotExtend implements Expression
{
    /** @var array<string> */
    private array $classNames;

    public function __construct(string ...$classNames)
    {
        $this->classNames = $classNames;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $desc = implode(', ', $this->classNames);

        return new Description("should not extend one of these classes: {$desc}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $extends = $theClass->getExtends();

        /** @var string $className */
        foreach ($this->classNames as $className) {
            $this->validatePattern($className);

            $hasParent = false;

            // If className contains wildcards, use pattern matching on direct parents only
            if (str_contains($className, '*') || str_contains($className, '?')) {
                foreach ($extends as $extend) {
                    if ($extend->matches($className)) {
                        $hasParent = true;
                        break;
                    }
                }
            } else {
                // Use is_a() to check the entire inheritance chain
                $hasParent = is_a($theClass->getFQCN(), $className, true);
            }

            if ($hasParent) {
                $violation = Violation::create(
                    $theClass->getFQCN(),
                    ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                    $theClass->getFilePath()
                );

                $violations->add($violation);
            }
        }
    }

    private function validatePattern(string $pattern): void
    {
        $validClassNameCharacters = '[a-zA-Z0-9_\x80-\xff]';
        $or = '|';
        $backslash = '\\\\';

        if (0 === preg_match('/^('.$validClassNameCharacters.$or.$backslash.$or.'\*'.$or.'\?)*$/', $pattern)) {
            throw new InvalidPatternException("'$pattern' is not a valid class or namespace pattern. Regex are not allowed, only * and ? wildcard.");
        }
    }
}
