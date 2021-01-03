<?php
declare(strict_types=1);

use Arkitect\CLI\Config;

return static function (Config $config): void {
    throw new RuntimeException('booom');
};
