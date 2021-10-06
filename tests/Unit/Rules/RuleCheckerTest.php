<?php
declare(strict_types=1);

namespace Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class RuleCheckerTest extends TestCase
{
    public function test_should_run_parse_on_all_files_in_class_set(): void
    {
        $this->markTestSkipped('invalid test row 80 hardcoded "uno"');
        $violations = new Violations();
        $fileParser = new FakeParser();
        $rule = new FakeRule();

        $runner = new Runner();

        $runner->check(
            ClassSetRules::create(new FakeClassSet(), ...[$rule]),
            new VoidProgress(),
            $fileParser,
            $violations
        );

        self::assertCount(3, $violations);
    }
}
