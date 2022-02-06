<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures;

use Arkitect\CLI\Config;

return static function (Config $config): void {
    throw new \RuntimeException('booom');
};
