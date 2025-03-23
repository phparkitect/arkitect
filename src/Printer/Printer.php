<?php
declare(strict_types=1);

namespace Arkitect\Printer;

interface Printer
{
    public const FORMAT_TEXT = 'text';

    public const FORMAT_JSON = 'json';

    public function print(array $violationsCollection): string;
}
