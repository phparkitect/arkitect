<?php

declare(strict_types=1);

namespace Arkitect\Tests\Report;

use Arkitect\Command\CheckResult;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\RuleResults;
use Arkitect\Evaluate\Selector;
use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsingError;
use Arkitect\Parser\ParsingErrors;
use Arkitect\Report\TextReport;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class TextReportTest extends TestCase
{
    /**
     * The reason is the group's title, so it is read once instead of being
     * repeated on every violation the way v1 does — and file:line leads each
     * line, because that is the part terminals and IDEs make clickable.
     */
    public function test_violations_are_grouped_under_the_reason_for_the_rule(): void
    {
        $classes = [
            ParsedClassFixture::create('App\Domain\Order', isFinal: false, line: 12, filePath: 'src/Domain/Order.php'),
            ParsedClassFixture::create('App\Domain\Invoice', isFinal: false, line: 8, filePath: 'src/Domain/Invoice.php'),
        ];

        $report = $this->render($classes, [
            Rule::allClasses()
                ->that(new Selector\ResideInNamespace('App\Domain'))
                ->should(new Constraint\IsFinal())
                ->because('domain objects are not meant to be extended'),
        ]);

        self::assertSame(<<<'OUT'
            ✗ domain objects are not meant to be extended
                src/Domain/Invoice.php:8  is not final
                src/Domain/Order.php:12   is not final

            2 classes, 1 rule, 2 violations in 1 rule
            OUT, $report);
    }

    /**
     * Two runs over the same code have to produce the same bytes, or every
     * diff of the output and every baseline becomes noise.
     */
    public function test_violations_are_ordered_by_file_then_line(): void
    {
        $classes = [
            ParsedClassFixture::create('App\Z', isFinal: false, line: 30, filePath: 'src/Z.php'),
            ParsedClassFixture::create('App\A', isFinal: false, line: 90, filePath: 'src/A.php'),
            ParsedClassFixture::create('App\M', isFinal: false, line: 5, filePath: 'src/M.php'),
        ];

        $report = $this->render($classes, [
            Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons'),
        ]);

        preg_match_all('/src\/\w+\.php:\d+/', $report, $matches);
        self::assertSame(['src/A.php:90', 'src/M.php:5', 'src/Z.php:30'], $matches[0]);
    }

    /**
     * Ten green lines would hide the two red ones. The summary already says
     * everything was checked.
     */
    public function test_a_rule_that_passes_is_not_printed(): void
    {
        $classes = [ParsedClassFixture::create('App\Order', isFinal: true)];

        $report = $this->render($classes, [
            Rule::allClasses()->should(new Constraint\IsFinal())->because('everything is final'),
        ]);

        self::assertStringNotContainsString('everything is final', $report);
        self::assertSame('✓ 1 class, 1 rule, no violations', $report);
    }

    /**
     * A setup problem, not an architectural one, so it is kept apart from the
     * violations and says what to do about it.
     */
    public function test_unresolved_classes_are_reported_apart_with_a_hint(): void
    {
        $class = ParsedClassFixture::create(
            'App\Api\Controller',
            extends: ['Vendor\NeverParsed'],
            line: 10,
            filePath: 'src/Api/Controller.php'
        );

        $report = $this->render([$class], [
            Rule::allClasses()->should(new Constraint\IsA('App\Contract'))->because('reasons'),
        ]);

        self::assertStringContainsString('! could not check 1 class', $report);
        self::assertStringContainsString('src/Api/Controller.php:10', $report);
        self::assertStringContainsString('vendor/ is probably outside the analysed root', $report);
    }

    public function test_a_rule_that_selected_nothing_is_called_out(): void
    {
        $report = $this->render([ParsedClassFixture::create('App\Order', isFinal: true)], [
            Rule::allClasses()
                ->that(new Selector\ResideInNamespace('App\Nowhere'))
                ->should(new Constraint\IsFinal())
                ->because('nothing lives here'),
        ]);

        self::assertStringContainsString('! "nothing lives here" matched no classes', $report);
    }

    public function test_a_rule_that_judged_nothing_is_called_out(): void
    {
        $report = $this->render([ParsedClassFixture::create('App\Repo', kind: ClassKind::Interface)], [
            Rule::allClasses()->should(new Constraint\IsFinal())->because('all final'),
        ]);

        self::assertStringContainsString('! "all final" matched 1 class and judged none', $report);
    }

    public function test_files_that_could_not_be_parsed_are_reported(): void
    {
        $check = new CheckResult(0, new ParsingErrors(new ParsingError('src/Broken.php', 'syntax error')), new RuleResults());

        $report = (new TextReport())->render($check);

        self::assertStringContainsString('! could not parse 1 file', $report);
        self::assertStringContainsString('src/Broken.php', $report);
    }

    /** @param list<\Arkitect\Parser\ParsedClass> $classes */
    private function render(array $classes, array $rules): string
    {
        $graph = new ParsedClassGraph(...$classes);
        $results = array_map(static fn (Rule $rule) => $rule->check($classes, $graph), $rules);

        return (new TextReport())->render(
            new CheckResult(\count($classes), new ParsingErrors(), new RuleResults(...$results))
        );
    }
}
