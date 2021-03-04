<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FilePath;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSet;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\RuleChecker;
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

        $fileParser->onClassAnalyzed(static function (ClassDescription $classDescription) use ($rule, $violations): void {
            $rule->check($classDescription, $violations);
        });

        $ruleChecker = new RuleChecker(new FakeClassSet(), $fileParser, new FilePath(), $violations, ...[$rule]);
        $violations = $ruleChecker->run();

        self::assertCount(3, $violations);
    }
}

class FakeClassSet extends ClassSet
{
    public function __construct()
    {
    }

    public function getIterator()
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
        $violations->add(Violation::create('fqcn', 'error'));
    }
}

class FakeParser implements Parser
{
    private $callback;

    public function parse(string $fileContent): void
    {
        \call_user_func($this->callback, ClassDescription::build('uno')->get());
    }

    public function onClassAnalyzed(callable $callable): void
    {
        $this->callback = $callable;
    }
}
