<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\ClassSet;
use PHPUnit\Framework\TestCase;

class ClassSetTest extends TestCase
{
    public function test_can_iterate_over_directories_recursively(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/../E2E/fixtures/happy_island');

        $files = iterator_to_array($set);

        self::assertEquals('BadCode', array_shift($files)->getFilenameWithoutExtension());
        self::assertEquals('HappyClass', array_shift($files)->getFilenameWithoutExtension());
        self::assertEquals('OtherBadCode', array_shift($files)->getFilenameWithoutExtension());
    }

    public function test_can_exclude_files_or_directories(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/../E2E/fixtures/mvc')
            ->exclude('Model', 'ContainerAwareInterface')
            ->exclude('/.*Catalog.*/');

        $expected = [
            'Controller/ProductsController.php',
            'Controller/UserController.php',
            'Services/UserService.php',
            'View/ProductView.php',
            'View/UserView.php',
        ];

        $actual = array_values(array_map(function ($item) {
            return $item->getRelativePathname();
        }, iterator_to_array($set)));

        self::assertEquals($expected, $actual);
    }
}
