<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\ClassSet;
use PHPUnit\Framework\TestCase;

class ClassSetTest extends TestCase
{
    public function testCanIterateOverDirectoriesRecursively(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/../E2E/fixtures/happy_island');

        $files = iterator_to_array($set);

        self::assertEquals('BadCode', array_shift($files)->getFilenameWithoutExtension());
        self::assertEquals('HappyClass', array_shift($files)->getFilenameWithoutExtension());
        self::assertEquals('OtherBadCode', array_shift($files)->getFilenameWithoutExtension());
    }
}
