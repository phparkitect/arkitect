<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionCollection;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class RuleCheckerTest extends TestCase
{
    public function test_should_run_parse_on_all_files_in_class_set(): void
    {
        $violations = new Violations();
        $fileParser = new FakeParser();
        $rule = new FakeRule();
        $parsingErrors = new ParsingErrors();

        $runner = new Runner();

        $runner->check(
            ClassSetRules::create(new FakeClassSet(), ...[$rule]),
            new VoidProgress(),
            $fileParser,
            $violations,
            $parsingErrors
        );

        self::assertCount(3, $violations);
    }
}

class FakeClassSet extends ClassSet
{
    public function __construct()
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            new FakeSplFileInfo('uno', '.', 'dir'),
            new FakeSplFileInfo('due', '.', 'dir'),
            new FakeSplFileInfo('tre', '.', 'dir'),
        ]);
    }
}

class FakeSplFileInfo extends SplFileInfo
{
    public function getContents(): string
    {
        return '';
    }
}

class FakeRule implements ArchRule
{
    public function check(ClassDescription $classDescription, Violations $violations, ClassDescriptionCollection $collection): void
    {
        $violations->add(Violation::create('fqcn', 'error'));
    }
}

class FakeParser implements Parser
{
    public function parse(string $fileContent, string $filename, array $classDescriptionToParse): array
    {
        return [
            ClassDescription::build('uno')->get(),
            ClassDescription::build('due')->get(),
            ClassDescription::build('tre')->get(),
        ];
    }

    public function getClassDescriptions(): array
    {
        return [ClassDescription::build('uno')->get()];
    }

    public function getParsingErrors(): array
    {
        return [];
    }

    public function getClassDescriptionsParsed(): ClassDescriptionCollection
    {
        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add(ClassDescription::build('uno')->get());
        $classDescriptionCollection->add(ClassDescription::build('due')->get());
        $classDescriptionCollection->add(ClassDescription::build('tre')->get());

        return $classDescriptionCollection;
    }
}
