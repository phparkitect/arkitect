<?php
declare(strict_types=1);

namespace Arkitect\CLI;

class Application extends \Symfony\Component\Console\Application
{
    private static $logo = <<< 'EOD'
  ____  _   _ ____   _         _    _ _            _
 |  _ \| | | |  _ \ / \   _ __| | _(_) |_ ___  ___| |_
 | |_) | |_| | |_) / _ \ | '__| |/ / | __/ _ \/ __| __|
 |  __/|  _  |  __/ ___ \| |  |   <| | ||  __/ (__| |_
 |_|   |_| |_|_| /_/   \_\_|  |_|\_\_|\__\___|\___|\__|
EOD;

    public function getLongVersion()
    {
        return sprintf("%s\n\n<info>%s</info> version <comment>%s</comment>", self::$logo, $this->getName(), $this->getVersion());
    }
}
