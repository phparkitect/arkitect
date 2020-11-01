<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\CLI\Check;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CLITest extends TestCase
{
    public function test_command_runs(): void
    {
        self::expectNotToPerformAssertions();

        $config = __DIR__.'/fixtures/config_02_mvc.php';

        $command = new Check();

        $input = new StringInput("--config=$config");
        $output = new BufferedOutput();

        $command->run($input, $output);
    }
}
