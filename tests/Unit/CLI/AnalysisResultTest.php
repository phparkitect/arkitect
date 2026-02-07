<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\AnalysisResult;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Rule;
use Arkitect\Rules\UnusedRules;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class AnalysisResultTest extends TestCase
{
    public function test_has_unused_rules_returns_false_when_no_unused_rules(): void
    {
        $result = new AnalysisResult(new Violations(), new ParsingErrors(), new UnusedRules());

        self::assertFalse($result->hasUnusedRules());
    }

    public function test_has_unused_rules_returns_true_when_unused_rules_exist(): void
    {
        $unusedRules = new UnusedRules();
        $unusedRules->add(
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\Legacy'))
                ->should(new HaveNameMatching('*Legacy'))
                ->because('legacy naming')
        );

        $result = new AnalysisResult(new Violations(), new ParsingErrors(), $unusedRules);

        self::assertTrue($result->hasUnusedRules());
    }

    public function test_unused_rules_are_accessible(): void
    {
        $unusedRules = new UnusedRules();
        $result = new AnalysisResult(new Violations(), new ParsingErrors(), $unusedRules);

        self::assertSame($unusedRules, $result->getUnusedRules());
    }

    public function test_unused_rules_defaults_to_empty_when_not_provided(): void
    {
        $result = new AnalysisResult(new Violations(), new ParsingErrors());

        self::assertFalse($result->hasUnusedRules());
        self::assertCount(0, $result->getUnusedRules());
    }

    public function test_unused_rules_do_not_affect_has_errors(): void
    {
        $unusedRules = new UnusedRules();
        $unusedRules->add(
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\Legacy'))
                ->should(new HaveNameMatching('*Legacy'))
                ->because('legacy naming')
        );

        $result = new AnalysisResult(new Violations(), new ParsingErrors(), $unusedRules);

        self::assertFalse($result->hasErrors());
    }
}
