<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FilePath;
use PHPUnit\Framework\TestCase;

class FilePathTest extends TestCase
{
    public function test_it_should_set_and_get_path(): void
    {
        $filePath = new FilePath();
        $filePath->set('thePath');

        self::assertEquals('thePath', $filePath->toString());
    }
}
