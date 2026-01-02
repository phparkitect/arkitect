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

class NotImplement implements Expression
{
    /** @var string */
    private $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("should not implement {$this->interface}", $because);
    }

    public function appliesTo(ClassDescription $theClass): bool
    {
        return !($theClass->isInterface() || $theClass->isTrait());
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($theClass->isInterface() || $theClass->isTrait()) {
            return;
        }

        $this->validatePattern($this->interface);

        $hasInterface = false;

        // If interface contains wildcards, use pattern matching on direct interfaces only
        if (str_contains($this->interface, '*') || str_contains($this->interface, '?')) {
            $interfaces = $theClass->getInterfaces();
            foreach ($interfaces as $interface) {
                if ($interface->matches($this->interface)) {
                    $hasInterface = true;
                    break;
                }
            }
        } else {
            // Use is_a() to check the entire inheritance chain
            $hasInterface = is_a($theClass->getFQCN(), $this->interface, true);
        }

        if ($hasInterface) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );
            $violations->add($violation);
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
