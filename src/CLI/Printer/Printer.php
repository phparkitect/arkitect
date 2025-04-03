<?php
declare(strict_types=1);

namespace Arkitect\CLI\Printer;

interface Printer
{
    public const FORMAT_TEXT = 'text';

    public const FORMAT_JSON = 'json';

    public const FORMAT_GITLAB = 'gitlab';

    public function print(array $violationsCollection): string;
}
