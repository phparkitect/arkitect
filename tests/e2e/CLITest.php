<?php
declare(strict_types=1);

namespace ArkitectTests\e2e;

use Arkitect\CLI\Check;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

class CLITest extends TestCase
{
    public function test_command(): void
    {
        self::expectNotToPerformAssertions();

        $config = __DIR__.'/fixtures/config_02_mvc.php';

        $command = new Check();

        $input = new StringInput("--config=$config");
        $output = new BufferedOutput();

        $command->run($input, $output);

        echo $output->fetch();
    }
}