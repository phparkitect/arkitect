<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\ClassSet;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ClassSetTest extends TestCase
{
    public function test_can_exclude_files_or_directories(): void
    {
        $path = $this->createMvcProjectStructure();

        $set = ClassSet::fromDir($path)
            ->excludePath('Model')
            ->excludePath('ContainerAwareInterface');

        $expected = [
            'Controller/CatalogController.php',
            'Controller/Foo.php',
            'Controller/ProductsController.php',
            'Controller/UserController.php',
            'Controller/YieldController.php',
            'Services/UserService.php',
            'View/CatalogView.php',
            'View/ProductView.php',
            'View/UserView.php',
        ];

        $actual = array_values(array_map(function ($item) {
            return $item->getRelativePathname();
        }, iterator_to_array($set)));

        self::assertEquals($expected, $actual);
    }

    public function test_can_exclude_glob_patterns(): void
    {
        $path = $this->createMvcProjectStructure();

        $set = ClassSet::fromDir($path)
            ->excludePath('*Catalog*');

        $expected = [
            'ContainerAwareInterface.php',
            'Controller/Foo.php',
            'Controller/ProductsController.php',
            'Controller/UserController.php',
            'Controller/YieldController.php',
            'Model/Products.php',
            'Model/Repository/ProductsRepository.php',
            'Model/Repository/UserRepository.php',
            'Model/User.php',
            'Services/UserService.php',
            'View/ProductView.php',
            'View/UserView.php',
        ];

        $actual = array_values(array_map(function ($item) {
            return $item->getRelativePathname();
        }, iterator_to_array($set)));

        self::assertEquals($expected, $actual);
    }

    protected function createMvcProjectStructure(): string
    {
        $structure = [
            'Controller' => [
                'CatalogController.php' => '',
                'Foo.php' => '',
                'ProductsController.php' => '',
                'UserController.php' => '',
                'YieldController.php' => '',
            ],
            'Model' => [
                'Repository' => [
                    'CatalogRepository.php' => '',
                    'ProductsRepository.php' => '',
                    'UserRepository.php' => '',
                ],
                'Catalog.php' => '',
                'Products.php' => '',
                'User.php' => '',
            ],
            'Services' => [
                'UserService.php' => '',
            ],
            'View' => [
                'CatalogView.php' => '',
                'ProductView.php' => '',
                'UserView.php' => '',
            ],
            'ContainerAwareInterface.php' => '',
        ];

        return vfsStream::setup('root', null, $structure)->url();
    }
}
