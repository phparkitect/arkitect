<?php

declare(strict_types=1);

namespace Arkitect\Tests\Cli;

use Arkitect\Cli\Arguments;
use PHPUnit\Framework\TestCase;

final class ArgumentsTest extends TestCase
{
    public function test_checking_is_what_it_does_without_being_asked(): void
    {
        self::assertSame('check', $this->parse()->command);
    }

    public function test_a_command_can_be_named(): void
    {
        self::assertSame('generate-baseline', $this->parse('generate-baseline')->command);
    }

    /**
     * The order getopt() cannot handle, and the one everybody types.
     */
    public function test_options_are_read_after_the_command(): void
    {
        $arguments = $this->parse('check', '--config=arch.php', '--skip-baseline');

        self::assertSame('check', $arguments->command);
        self::assertSame('arch.php', $arguments->value('config', 'default.php'));
        self::assertTrue($arguments->has('skip-baseline'));
    }

    public function test_an_absent_option_falls_back(): void
    {
        self::assertSame('phparkitect.php', $this->parse()->value('config', 'phparkitect.php'));
        self::assertFalse($this->parse()->has('skip-baseline'));
    }

    /**
     * A mistyped flag that is quietly ignored is a run that did something
     * other than what was asked.
     */
    public function test_an_unknown_option_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('--skpi-baseline');

        $this->parse('check', '--skpi-baseline');
    }

    public function test_an_unknown_command_is_refused_and_lists_the_real_ones(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('generate-baseline');

        $this->parse('chekc');
    }

    public function test_an_option_that_needs_a_value_says_so(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('needs a value');

        $this->parse('--config');
    }

    public function test_a_flag_given_a_value_says_so(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('takes no value');

        $this->parse('--skip-baseline=yes');
    }

    public function test_a_value_may_contain_an_equals_sign(): void
    {
        self::assertSame('a=b.php', $this->parse('--config=a=b.php')->value('config', ''));
    }

    private function parse(string ...$arguments): Arguments
    {
        return Arguments::fromArgv(['bin/arkitect', ...$arguments]);
    }
}
