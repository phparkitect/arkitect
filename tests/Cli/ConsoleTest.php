<?php

declare(strict_types=1);

namespace Arkitect\Tests\Cli;

use Arkitect\Cli\Console;
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
            null === $baseline ? '' : \sprintf("->baseline('%s')", $baseline)
        );
    }
}
