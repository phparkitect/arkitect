<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassHierarchyResolver;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Utils\MockHierarchyResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class RuleCheckerTest extends TestCase
{
    use MockHierarchyResolver;
    public function test_should_run_parse_on_all_files_in_class_set(): void
    {
        $violations = new Violations();
        $fileParser = new FakeParser($this->createMockResolver());
        $rule = new FakeRule();
        $parsingErrors = new ParsingErrors();
        $stopOnFailure = false;

        $runner = new Runner();

        $runner->check(
            ClassSetRules::create(new FakeClassSet(), ...[$rule]),
            new VoidProgress(),
            $fileParser,
            $violations,
            $parsingErrors,
            $stopOnFailure
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
    private ClassHierarchyResolver $resolver;

    public function __construct(ClassHierarchyResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function parse(string $fileContent, string $filename): void
    {
    }

    public function getClassDescriptions(): array
    {
        return [ClassDescription::getBuilder('uno', 'src/Foo.php', $this->resolver)->build()];
    }

    public function getParsingErrors(): array
    {
        return [];
    }
}
