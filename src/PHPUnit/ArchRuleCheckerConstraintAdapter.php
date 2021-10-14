<?php
declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Constraint\Constraint;

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

    public function __construct(ClassSet $classSet)
    {
        $targetPhpVersion = TargetPhpVersion::create(null);
        $this->runner = new Runner();
        $this->fileparser = FileParserFactory::createFileParser($targetPhpVersion);
        $this->classSet = $classSet;
        $this->violations = new Violations();
    }

    public function toString(): string
    {
        return 'satisfies all architectural constraints';
    }

    protected function matches(/** @var $rule ArchRule */ $other): bool
    {
        $this->runner->check(
            ClassSetRules::create($this->classSet, $other),
            new VoidProgress(),
            $this->fileparser,
            $this->violations
        );

        return 0 === $this->violations->count();
    }

    protected function failureDescription($other): string
    {
        return "\n".$this->violations->toString();
    }
}
