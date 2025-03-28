<?php

declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Printer\Printer;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * @psalm-suppress UndefinedClass
 *
 * Since we declared phpunit as a dev dependency we cannot be sure the class PHPUnit\Framework\Constraint\Constraint
 * will be available at runtime. Given arkitect will be used in a development environment, we can ignore this but it
 * would be nice to have a way to check if the class is available at runtime.
 */
class ArchRuleCheckerConstraintAdapter extends Constraint
{
    /** @var ClassSet */
    private $classSet;

    /** @var Violations */
    private $violations;

    /** @var Runner */
    private $runner;

    /** @var FileParser */
    private $fileparser;

    /** @var ParsingErrors */
    private $parsingErrors;

    public function __construct(ClassSet $classSet)
    {
        $targetPhpVersion = TargetPhpVersion::create(null);
        $this->runner = new Runner();
        $this->fileparser = FileParserFactory::createFileParser($targetPhpVersion);
        $this->classSet = $classSet;
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();
    }

    public function toString(): string
    {
        return 'satisfies all architectural constraints';
    }

    protected function matches(
        /** @var $rule ArchRule */
        $other
    ): bool {
        $this->runner->check(
            ClassSetRules::create($this->classSet, $other),
            new VoidProgress(),
            $this->fileparser,
            $this->violations,
            $this->parsingErrors
        );

        $violationsCount = $this->violations->count();
        $parsingErrorsCount = $this->parsingErrors->count();

        return 0 === $violationsCount && 0 === $parsingErrorsCount;
    }

    protected function failureDescription($other): string
    {
        if ($this->parsingErrors->count() > 0) {
            return "\n".$this->parsingErrors->toString();
        }

        return "\n".$this->violations->toString(Printer::FORMAT_TEXT);
    }
}
