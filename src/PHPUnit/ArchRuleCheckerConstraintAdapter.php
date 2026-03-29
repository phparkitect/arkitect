<?php

declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\Analyzer\Parser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\ParsingErrors;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Printer\Printer;
use Arkitect\CLI\Printer\PrinterFactory;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
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
    private ClassSet $classSet;

    private Violations $violations;

    private Runner $runner;

    private Parser $fileparser;

    private ParsingErrors $parsingErrors;

    private Printer $printer;

    public function __construct(ClassSet $classSet)
    {
        $targetPhpVersion = TargetPhpVersion::create(null);
        $this->runner = new Runner();
        $this->fileparser = FileParserFactory::createFileParser($targetPhpVersion, true, null);
        $this->classSet = $classSet;
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();
        $this->printer = PrinterFactory::create(Printer::FORMAT_TEXT);
    }

    public function toString(): string
    {
        return 'satisfies all architectural constraints';
    }

    protected function matches(
        /** @var ArchRule $rule */
        $other,
    ): bool {
        $this->runner->check(
            ClassSetRules::create($this->classSet, $other),
            new VoidProgress(),
            $this->fileparser,
            $this->violations,
            $this->parsingErrors,
            false
        );

        $violationsCount = $this->violations->count();
        $parsingErrorsCount = $this->parsingErrors->count();

        return 0 === $violationsCount && 0 === $parsingErrorsCount;
    }

    protected function failureDescription($other): string
    {
        if ($this->parsingErrors->count() > 0) {
            $result = "\n parsing error: ";
            foreach ($this->parsingErrors as $parsingError) {
                $result .= "\n$parsingError";
            }

            return $result;
        }

        return "\n".$this->printer->print($this->violations->groupedByFqcn());
    }
}
