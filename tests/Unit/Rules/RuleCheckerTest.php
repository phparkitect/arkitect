<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Rule;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Fixtures\Animal\AnimalInterface;
use Arkitect\Tests\Fixtures\Fruit\CavendishBanana;
use Arkitect\Tests\Fixtures\Fruit\DwarfCavendishBanana;
use Arkitect\Tests\Fixtures\Fruit\FruitInterface;
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

    public function test_can_exclude_files_or_directories_from_multiple_dir_class_set_with_no_violations(): void
    {
        $classSet = ClassSet::fromDir(__DIR__ . '/../../Fixtures');

        $rules[] = Rule::allClasses()
            ->except(FruitInterface::class, CavendishBanana::class, DwarfCavendishBanana::class)
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\Fruit'))
            ->should(new Implement(FruitInterface::class))
            ->because('this tests that string exceptions fail');

        $rules[] = Rule::allClasses()
            ->exceptExpression(new HaveNameMatching('*TestCase'))
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\Animal'))
            ->should(new Implement(AnimalInterface::class))
            ->because('this tests that expression exceptions fail');

        $runner = new Runner();

        $runner->check(
            ClassSetRules::create($classSet, ...$rules),
            new VoidProgress(),
            FileParserFactory::createFileParser(TargetPhpVersion::create(null)),
            $violations = new Violations(),
            new ParsingErrors()
        );

        self::assertCount(0, $violations);
    }

    public function test_can_exclude_files_or_directories_from_multiple_dir_class_set_with_violations(): void
    {
        $classSet = ClassSet::fromDir(__DIR__ . '/../../Fixtures');

        $rules[] = Rule::allClasses()
            ->except(FruitInterface::class, CavendishBanana::class)
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\Fruit'))
            ->should(new Implement(FruitInterface::class))
            ->because('this tests that string exceptions fail');

        $rules[] = Rule::allClasses()
            ->exceptExpression(new HaveNameMatching('*NotExistingSoItFails'))
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\Animal'))
            ->should(new Implement(AnimalInterface::class))
            ->because('this tests that expression exceptions fail');

        $runner = new Runner();

        $runner->check(
            ClassSetRules::create($classSet, ...$rules),
            new VoidProgress(),
            FileParserFactory::createFileParser(TargetPhpVersion::create(null)),
            $violations = new Violations(),
            new ParsingErrors()
        );

        self::assertCount(2, $violations);
        $expectedViolations = "Arkitect\Tests\Fixtures\Animal\CatTestCase has 1 violations
            should implement Arkitect\Tests\Fixtures\Animal\AnimalInterface because this tests
            that expression exceptions fail Arkitect\Tests\Fixtures\Fruit\DwarfCavendishBanana has 1 violations
            should implement Arkitect\Tests\Fixtures\Fruit\FruitInterface because
            this tests that string exceptions fail";
        self::assertEquals(
            preg_replace('/\s+/', ' ', $expectedViolations),
            preg_replace('/\s+/', ' ', trim($violations->toString()))
        );
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
    public function check(ClassDescription $classDescription, Violations $violations): void
    {
        $violations->add(new Violation('fqcn', 'error'));
    }

    public function isRunOnlyThis(): bool
    {
        return false;
    }

    public function runOnlyThis(): ArchRule
    {
        return $this;
    }
}

class FakeParser implements Parser
{
    public function parse(string $fileContent, string $filename): void
    {
    }

    public function getClassDescriptions(): array
    {
        return [ClassDescription::getBuilder('uno')->build()];
    }

    public function getParsingErrors(): array
    {
        return [];
    }
}
