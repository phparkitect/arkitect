<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\CLI\Command\Check;
use Arkitect\CLI\Command\DebugExpression;
use Arkitect\CLI\Command\Init;

class PhpArkitectApplication extends \Symfony\Component\Console\Application
{
    private static string $logo = <<< 'EOD'
     ____  _   _ ____   _         _    _ _            _
    |  _ \| | | |  _ \ / \   _ __| | _(_) |_ ___  ___| |_
    | |_) | |_| | |_) / _ \ | '__| |/ / | __/ _ \/ __| __|
    |  __/|  _  |  __/ ___ \| |  |   <| | ||  __/ (__| |_
    |_|   |_| |_|_| /_/   \_\_|  |_|\_\_|\__\___|\___|\__|
    EOD;

    public function __construct()
    {
        parent::__construct('PHPArkitect', Version::get());

        // Symfony 8 removed add() in favor of addCommand()
        // addCommand() was introduced in Symfony 7.4
        $addMethod = method_exists($this, 'addCommand') ? 'addCommand' : 'add';

        $this->$addMethod(new Check());
        $this->$addMethod(new Init());
        $this->$addMethod(new DebugExpression());
    }

    public function getLongVersion(): string
    {
        return \sprintf("%s\n\n<info>%s</info> version <comment>%s</comment>", self::$logo, $this->getName(), $this->getVersion());
    }
}
