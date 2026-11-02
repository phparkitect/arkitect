<?php

declare(strict_types=1);

namespace Arkitect\Tests\Report;

use Arkitect\Command\CheckResult;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\RuleResults;
use Arkitect\Evaluate\Selector;
use Arkitect\Parser\ParsingError;
use Arkitect\Parser\ParsingErrors;
use Arkitect\Report\GitlabReport;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * The format is GitLab's Code Quality report, so the shape is the contract:
 * a single JSON array whose entries carry the five fields GitLab reads.
 */
final class GitlabReportTest extends TestCase
{
    public function test_a_clean_run_is_an_empty_array(): void
    {
        self::assertSame([], $this->entries([], []));
    }

    public function test_a_violation_carries_the_five_fields_gitlab_reads(): void
    {
        $entries = $this->entries(
            [ParsedClassFixture::create('App\Order', isFinal: false, line: 12, filePath: 'src/Order.php')],
            [Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons')]
        );

        self::assertCount(1, $entries);
        self::assertSame(
            ['description', 'check_name', 'fingerprint', 'severity', 'location'],
            array_keys($entries[0])
        );
        self::assertSame(['path' => 'src/Order.php', 'lines' => ['begin' => 12]], $entries[0]['location']);
        self::assertSame('major', $entries[0]['severity']);
    }

    /**
     * check_name names the check and never the finding, so it is the same
     * string for every occurrence — which is what lets GitLab filter by it.
     */
    public function test_check_name_is_the_constraint(): void
    {
        $entries = $this->entries(
            [
                ParsedClassFixture::create('App\Order', isFinal: false),
                ParsedClassFixture::create('App\Invoice', isFinal: false),
            ],
            [Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons')]
        );

        self::assertSame(Constraint\IsFinal::class, $entries[0]['check_name']);
        self::assertSame(Constraint\IsFinal::class, $entries[1]['check_name']);
    }

    public function test_two_different_classes_get_two_fingerprints(): void
    {
        $entries = $this->entries(
            [
                ParsedClassFixture::create('App\Order', isFinal: false),
                ParsedClassFixture::create('App\Invoice', isFinal: false),
            ],
            [Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons')]
        );

        self::assertNotSame($entries[0]['fingerprint'], $entries[1]['fingerprint']);
    }

    /**
     * GitLab groups by fingerprint across commits, so it has to survive the
     * code moving. A line shifts whenever anything above it does, and a
     * fingerprint carrying it would make every old finding look new on the
     * next merge request — the same reason the baseline refuses to key on it.
     */
    public function test_the_fingerprint_survives_the_line_moving(): void
    {
        $rule = Rule::allClasses()
            ->should(new Constraint\NotDependOnTheseNamespaces(['App\Infra']))
            ->because('reasons');

        $before = $this->entries(
            [ParsedClassFixture::create('App\Order', dependencies: ['App\Infra\Db' => 9], filePath: 'src/Order.php')],
            [$rule]
        );

        $after = $this->entries(
            [ParsedClassFixture::create('App\Order', dependencies: ['App\Infra\Db' => 41], filePath: 'src/Order.php')],
            [$rule]
        );

        self::assertSame(9, $before[0]['location']['lines']['begin']);
        self::assertSame(41, $after[0]['location']['lines']['begin']);
        self::assertSame($before[0]['fingerprint'], $after[0]['fingerprint']);
    }

    /**
     * One violation per offending dependency, each on its own line, each
     * its own entry — the shape that made the text report useful survives.
     */
    public function test_one_entry_per_offending_dependency(): void
    {
        $entries = $this->entries(
            [ParsedClassFixture::create('App\Order', dependencies: ['App\Infra\Db' => 9, 'App\Infra\Http' => 14])],
            [Rule::allClasses()->should(new Constraint\NotDependOnTheseNamespaces(['App\Infra']))->because('reasons')]
        );

        self::assertCount(2, $entries);
        self::assertSame([9, 14], [$entries[0]['location']['lines']['begin'], $entries[1]['location']['lines']['begin']]);
        self::assertNotSame($entries[0]['fingerprint'], $entries[1]['fingerprint']);
    }

    /**
     * A run that could not read a file exits non-zero, so a report leaving
     * it out would show a clean merge request for a failed run.
     */
    public function test_a_parsing_error_is_an_entry(): void
    {
        $check = new CheckResult(0, new ParsingErrors(new ParsingError('src/Broken.php', 'syntax error')), new RuleResults());

        $entries = $this->decode((new GitlabReport())->render($check));

        self::assertCount(1, $entries);
        self::assertSame('src/Broken.php', $entries[0]['location']['path']);
        self::assertSame(ParsingError::class, $entries[0]['check_name']);
        self::assertSame('syntax error', $entries[0]['description']);
    }

    public function test_a_class_that_could_not_be_resolved_is_an_entry(): void
    {
        $entries = $this->entries(
            [ParsedClassFixture::create('App\Handler', extends: ['Vendor\Missing'], filePath: 'src/Handler.php')],
            [Rule::allClasses()->that(new Selector\IsA('Vendor\Base'))->should(new Constraint\IsFinal())->because('reasons')]
        );

        self::assertCount(1, $entries);
        self::assertSame('src/Handler.php', $entries[0]['location']['path']);
        self::assertSame(\Arkitect\Evaluate\UnresolvedClass::class, $entries[0]['check_name']);
    }

    public function test_the_output_is_one_json_array(): void
    {
        $rendered = (new GitlabReport())->render(
            new CheckResult(0, new ParsingErrors(), new RuleResults())
        );

        self::assertSame('[]', trim($rendered));
    }

    /**
     * @param list<\Arkitect\Parser\ParsedClass> $classes
     * @param list<Rule>                         $rules
     *
     * @return list<array<string, mixed>>
     */
    private function entries(array $classes, array $rules): array
    {
        $graph = new ParsedClassGraph(...$classes);
        $results = array_map(static fn (Rule $rule) => $rule->check($classes, $graph), $rules);

        return $this->decode((new GitlabReport())->render(
            new CheckResult(\count($classes), new ParsingErrors(), new RuleResults(...$results))
        ));
    }

    /** @return list<array<string, mixed>> */
    private function decode(string $rendered): array
    {
        return json_decode($rendered, true, flags: \JSON_THROW_ON_ERROR);
    }
}
