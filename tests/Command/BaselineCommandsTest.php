<?php

declare(strict_types=1);

namespace Arkitect\Tests\Command;

use Arkitect\Command\Check;
use Arkitect\Command\GenerateBaseline;
use Arkitect\Command\PruneBaseline;
use Arkitect\Config;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use Arkitect\Parser\RepositoryParser;
use Arkitect\Tests\FileSystem\InMemoryBaselineRepository;
use Arkitect\Tests\FileSystem\InMemoryFileRepository;
use PHPUnit\Framework\TestCase;

final class BaselineCommandsTest extends TestCase
{
    public function test_generating_accepts_everything_currently_wrong(): void
    {
        $baselines = new InMemoryBaselineRepository();
        $config = $this->config();

        $accepted = $this->generate(['src/A.php' => $this->aClass('A'), 'src/B.php' => $this->aClass('B')], $baselines, $config);

        self::assertSame(2, $accepted);
        self::assertTrue($this->check($this->files(['src/A.php' => $this->aClass('A'), 'src/B.php' => $this->aClass('B')]), $baselines)
            ->run($config)
            ->isClean());
    }

    /**
     * Regenerating means "accept what is here now", so it has to see through
     * the baseline it is about to replace — reading the old one would hide
     * exactly the violations it exists to record.
     */
    public function test_generating_again_sees_what_the_old_baseline_hides(): void
    {
        $baselines = new InMemoryBaselineRepository();
        $config = $this->config();
        $files = ['src/A.php' => $this->aClass('A')];

        $this->generate($files, $baselines, $config);
        $files['src/B.php'] = $this->aClass('B');

        self::assertSame(2, $this->generate($files, $baselines, $config));
    }

    public function test_pruning_drops_entries_that_match_nothing_any_more(): void
    {
        $baselines = new InMemoryBaselineRepository();
        $config = $this->config();
        $files = ['src/A.php' => $this->aClass('A'), 'src/B.php' => $this->aClass('B')];

        $this->generate($files, $baselines, $config);

        // B is fixed, so its entry now stands for nothing
        $files['src/B.php'] = "<?php\nnamespace App;\nfinal class B {}\n";

        $dropped = (new PruneBaseline($this->check($this->files($files), $baselines), $baselines))->run($config);

        self::assertSame(1, $dropped);
        self::assertCount(1, $baselines->read('baseline.json'));
    }

    /**
     * The difference from regenerating, and the reason both exist: pruning
     * only ever shrinks, so work done since is not quietly accepted.
     */
    public function test_pruning_never_accepts_a_new_violation(): void
    {
        $baselines = new InMemoryBaselineRepository();
        $config = $this->config();

        $this->generate(['src/A.php' => $this->aClass('A')], $baselines, $config);

        $files = ['src/A.php' => $this->aClass('A'), 'src/B.php' => $this->aClass('B')];
        (new PruneBaseline($this->check($this->files($files), $baselines), $baselines))->run($config);

        self::assertCount(1, $baselines->read('baseline.json'));
        self::assertFalse($this->check($this->files($files), $baselines)->run($config)->isClean());
    }

    public function test_a_config_without_a_baseline_path_says_so(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('add baseline()');

        $baselines = new InMemoryBaselineRepository();

        (new GenerateBaseline($this->check($this->files([]), $baselines), $baselines))
            ->run(Config::create(__DIR__)->add([$this->aRule()]));
    }

    /** @param array<string, string> $files */
    private function generate(array $files, InMemoryBaselineRepository $baselines, Config $config): int
    {
        return (new GenerateBaseline($this->check($this->files($files), $baselines), $baselines))->run($config);
    }

    /** @param array<string, string> $files */
    private function files(array $files): InMemoryFileRepository
    {
        $repository = new InMemoryFileRepository();

        foreach ($files as $path => $contents) {
            $repository = $repository->withFile($path, $contents);
        }

        return $repository;
    }

    private function check(InMemoryFileRepository $files, InMemoryBaselineRepository $baselines): Check
    {
        return new Check(new RepositoryParser($files), $baselines);
    }

    private function config(): Config
    {
        return Config::create(__DIR__)->add([$this->aRule()])->baseline('baseline.json');
    }

    private function aRule(): Rule
    {
        return Rule::allClasses()->should(new Constraint\IsFinal())->because('everything is final');
    }

    private function aClass(string $name): string
    {
        return \sprintf("<?php\nnamespace App;\nclass %s {}\n", $name);
    }
}
