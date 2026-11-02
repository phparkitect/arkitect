<?php

declare(strict_types=1);

namespace Arkitect\Tests\Cli;

use Arkitect\Cli\Console;
use Arkitect\Config;
use PHPUnit\Framework\TestCase;

/**
 * Exercised against a real directory and a real config file, the way
 * FilesystemFileRepositoryTest is: an adapter that is only ever tested
 * through a double is an adapter nobody checked.
 */
final class ConsoleTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/arkitect-cli-'.bin2hex(random_bytes(6));

        mkdir($this->root.'/src', 0o777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/{,.}*', \GLOB_BRACE) ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        foreach (glob($this->root.'/src/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($this->root.'/src');
        rmdir($this->root);
    }

    public function test_a_codebase_that_satisfies_its_rules_exits_clean(): void
    {
        $this->write('src/Order.php', "<?php\nnamespace App;\nfinal class Order {}\n");
        $this->write('arch.php', $this->config());

        [$code, $out] = $this->arkitect('check');

        self::assertSame(0, $code);
        self::assertStringContainsString('no violations', $out);
    }

    public function test_a_violation_exits_one_and_says_where(): void
    {
        $this->write('src/Order.php', "<?php\nnamespace App;\nclass Order {}\n");
        $this->write('arch.php', $this->config());

        [$code, $out] = $this->arkitect('check');

        self::assertSame(1, $code);
        self::assertStringContainsString('src/Order.php:3', $out);
    }

    /** Checking is what it does when nothing else is asked. */
    public function test_the_command_can_be_left_out(): void
    {
        $this->write('src/Order.php', "<?php\nnamespace App;\nclass Order {}\n");
        $this->write('arch.php', $this->config());

        self::assertSame(1, $this->arkitect()[0]);
    }

    public function test_generating_then_checking_goes_clean_and_skipping_shows_it_again(): void
    {
        $this->write('src/Order.php', "<?php\nnamespace App;\nclass Order {}\n");
        $this->write('arch.php', $this->config(baseline: 'baseline.json'));

        [$code, $out] = $this->arkitect('generate-baseline');
        self::assertSame(0, $code);
        self::assertStringContainsString('accepted 1', $out);

        self::assertSame(0, $this->arkitect('check')[0]);
        self::assertSame(1, $this->arkitect('check', '--skip-baseline')[0]);
    }

    public function test_a_missing_config_is_a_usage_error_not_a_crash(): void
    {
        [$code, , $err] = $this->arkitect('check');

        self::assertSame(2, $code);
        self::assertStringContainsString('No config at', $err);
    }

    public function test_a_config_that_returns_the_wrong_thing_says_what_it_returned(): void
    {
        $this->write('arch.php', "<?php\n\nreturn ['not', 'a', 'config'];\n");

        [$code, , $err] = $this->arkitect('check');

        self::assertSame(2, $code);
        self::assertStringContainsString('has to return a Config', $err);
    }

    public function test_help_asks_for_nothing_and_exits_clean(): void
    {
        [$code, $out] = $this->arkitect('--help');

        self::assertSame(0, $code);
        self::assertStringContainsString('generate-baseline', $out);
    }

    public function test_the_gitlab_format_prints_a_code_quality_array(): void
    {
        $this->write('arch.php', $this->config());
        $this->write('Order.php', "<?php\nnamespace App;\nclass Order {}\n");

        [$code, $out] = $this->arkitect('--format=gitlab');

        self::assertSame(1, $code);

        $entries = json_decode($out, true, flags: \JSON_THROW_ON_ERROR);

        self::assertCount(1, $entries);
        self::assertSame('Order.php', $entries[0]['location']['path']);
    }

    /**
     * A format nobody implements is a misuse, not an empty report: the run
     * would otherwise exit as if it had said something.
     */
    public function test_an_unknown_format_is_refused(): void
    {
        $this->write('arch.php', $this->config());

        [$code, , $err] = $this->arkitect('--format=json');

        self::assertSame(2, $code);
        self::assertStringContainsString('Unknown format "json"', $err);
    }

    public function test_the_help_lists_the_formats(): void
    {
        [, $out] = $this->arkitect('--help');

        self::assertStringContainsString('--format', $out);
        self::assertStringContainsString('gitlab', $out);
    }

    /**
     * The adoption path, end to end and with nothing in the config: accept
     * what is there, then pass.
     */
    public function test_a_generated_baseline_is_picked_up_without_being_declared(): void
    {
        $this->write('arch.php', $this->config());
        $this->write('Order.php', "<?php\nnamespace App;\nclass Order {}\n");

        [$code, $out] = $this->arkitect('generate-baseline');

        self::assertSame(0, $code);
        self::assertStringContainsString('accepted 1 violations', $out);
        self::assertFileExists($this->root.'/'.Config::DEFAULT_BASELINE);

        [$code] = $this->arkitect();

        self::assertSame(0, $code);
    }

    public function test_skip_baseline_ignores_the_one_it_found(): void
    {
        $this->write('arch.php', $this->config());
        $this->write('Order.php', "<?php\nnamespace App;\nclass Order {}\n");

        $this->arkitect('generate-baseline');

        [$code, $out] = $this->arkitect('--skip-baseline');

        self::assertSame(1, $code);
        self::assertStringContainsString('is not final', $out);
    }

    /**
     * The suggested command is the one that was just run, so it can be
     * copied out of the output. A hardcoded name would be wrong for a
     * composer install, a phar, or a global bin.
     */
    public function test_the_suggested_command_is_the_one_that_was_invoked(): void
    {
        $this->write('arch.php', $this->config());
        $this->write('Order.php', "<?php\nnamespace App;\nclass Order {}\n");

        $this->arkitect('generate-baseline');
        $this->write('Order.php', "<?php\nnamespace App;\nfinal class Order {}\n");

        [, $out] = $this->arkitect();

        // the helper always passes --config, so the suggestion has to carry it
        self::assertStringContainsString('run `bin/arkitect --config='.$this->root.'/arch.php prune-baseline`', $out);
    }

    /** @return array{int, string, string} */
    private function arkitect(string ...$arguments): array
    {
        $out = fopen('php://memory', 'r+');
        $err = fopen('php://memory', 'r+');

        $code = (new Console())->run(
            ['bin/arkitect', ...$arguments, '--config='.$this->root.'/arch.php'],
            $out,
            $err
        );

        rewind($out);
        rewind($err);

        return [$code, (string) stream_get_contents($out), (string) stream_get_contents($err)];
    }

    private function write(string $path, string $contents): void
    {
        file_put_contents($this->root.'/'.$path, $contents);
    }

    private function config(?string $baseline = null): string
    {
        return \sprintf(
            "<?php\n\nreturn \\Arkitect\\Config::create('%s')%s->add([\n"
            ."    \\Arkitect\\Evaluate\\Rule::allClasses()\n"
            ."        ->should(new \\Arkitect\\Evaluate\\Constraint\\IsFinal())\n"
            ."        ->because('everything is final'),\n"
            ."]);\n",
            $this->root,
            null === $baseline ? '' : \sprintf("->withBaseline('%s')", $baseline)
        );
    }
}
